<?php

namespace App\Http\Controllers\Reseller;

use App\Constants\Status;
use App\Http\Controllers\Controller;
use App\Models\AccountListing;
use App\Models\Deposit;
use App\Models\Transaction;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class ResellerController extends Controller
{
    public function dashboard()
    {
        $pageTitle = 'Reseller Dashboard';
        $reseller = auth()->user();

        // Statistics
        $totalClients = User::where('reseller_id', $reseller->id)->count();
        $activeClients = User::where('reseller_id', $reseller->id)
            ->where('status', Status::USER_ACTIVE)
            ->where(function ($q) {
                $q->whereNull('expires_at')->orWhere('expires_at', '>=', now());
            })->count();

        $expiredClients = User::where('reseller_id', $reseller->id)
            ->whereNotNull('expires_at')
            ->where('expires_at', '<', now())
            ->count();

        $totalSpent = Transaction::where('user_id', $reseller->id)
            ->where('trx_type', '-')
            ->sum('amount');

        $recentClients = User::where('reseller_id', $reseller->id)
            ->orderBy('id', 'desc')
            ->limit(5)
            ->get();

        $recentTransactions = Transaction::where('user_id', $reseller->id)
            ->orderBy('id', 'desc')
            ->limit(5)
            ->get();

        $accounts = AccountListing::with('socialMedia')
            ->active()
            ->orderBy('social_media_id', 'asc')
            ->get();

        return view('reseller.dashboard', compact(
            'pageTitle', 'reseller', 'totalClients', 'activeClients',
            'expiredClients', 'totalSpent', 'recentClients', 'recentTransactions', 'accounts'
        ));
    }

    public function users(Request $request)
    {
        $pageTitle = 'My Client Users';
        $reseller = auth()->user();

        $query = User::where('reseller_id', $reseller->id);

        if ($request->search) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('username', 'like', "%$search%")
                    ->orWhere('email', 'like', "%$search%")
                    ->orWhere('firstname', 'like', "%$search%")
                    ->orWhere('lastname', 'like', "%$search%");
            });
        }

        if ($request->status == 'active') {
            $query->where('status', Status::USER_ACTIVE)
                ->where(function ($q) {
                    $q->whereNull('expires_at')->orWhere('expires_at', '>=', now());
                });
        } elseif ($request->status == 'expired') {
            $query->whereNotNull('expires_at')->where('expires_at', '<', now());
        } elseif ($request->status == 'banned') {
            $query->where('status', Status::USER_BAN);
        }

        $users = $query->orderBy('id', 'desc')->paginate(getPaginate());
        $accounts = AccountListing::with('socialMedia')->active()->get()->keyBy('id');

        return view('reseller.users.index', compact('pageTitle', 'users', 'accounts', 'reseller'));
    }

    public function createUser()
    {
        $pageTitle = 'Create New Client User';
        $reseller = auth()->user();
        $domain = parse_url(config('app.url') ?: url('/'), PHP_URL_HOST) ?: request()->getHost();

        $accounts = AccountListing::with('socialMedia')
            ->active()
            ->where('cookie_status', '!=', 0)
            ->orderBy('social_media_id', 'asc')
            ->get();

        return view('reseller.users.create', compact('pageTitle', 'reseller', 'domain', 'accounts'));
    }

    public function storeUser(Request $request)
    {
        $reseller = auth()->user();
        $domain = parse_url(config('app.url') ?: url('/'), PHP_URL_HOST) ?: request()->getHost();

        $request->validate([
            'name'          => 'required|string|max:80',
            'email_prefix'  => 'required|string|max:60',
            'password'      => 'required|string|min:4',
            'duration_days' => 'required|integer|in:30,60,90,180,365',
            'account_ids'   => 'required|array|min:1',
            'account_ids.*' => 'integer|exists:account_listings,id',
        ], [
            'account_ids.required' => 'Please select at least one platform account to assign to this client.',
        ]);

        $prefix = strtolower(preg_replace('/[^a-z0-9._-]/i', '', trim($request->email_prefix)));
        if (empty($prefix)) {
            $notify[] = ['error', 'Please provide a valid username / email prefix.'];
            return back()->withNotify($notify)->withInput();
        }

        $email = $prefix . '@' . $domain;
        $username = $prefix;

        if (User::where('email', $email)->exists() || User::where('username', $username)->exists()) {
            $notify[] = ['error', 'The username/email "' . $prefix . '" is already taken. Please choose another prefix.'];
            return back()->withNotify($notify)->withInput();
        }

        $accountIds = array_values(array_map('intval', $request->account_ids));
        $resellerPrices = (array) ($reseller->account_prices ?? []);

        // Calculate Monthly Unit Cost
        $monthlyCost = 0.00;
        foreach ($accountIds as $accId) {
            $monthlyCost += isset($resellerPrices[$accId]) ? (float) $resellerPrices[$accId] : 0.00;
        }

        $durationDays = (int) $request->duration_days;
        $months = $durationDays / 30;
        $totalCost = round($monthlyCost * $months, 2);

        if ($totalCost > $reseller->balance) {
            $notify[] = ['error', 'Insufficient wallet balance. Total cost is ' . showAmount($totalCost) . ' but your balance is ' . showAmount($reseller->balance) . '. Please recharge your wallet.'];
            return back()->withNotify($notify)->withInput();
        }

        $name = trim($request->name);
        $nameParts = array_values(array_filter(explode(' ', $name)));
        $firstname = $nameParts[0] ?? 'User';
        $lastname  = isset($nameParts[1]) ? implode(' ', array_slice($nameParts, 1)) : 'Client';

        DB::beginTransaction();
        try {
            // Deduct Wallet Balance
            if ($totalCost > 0) {
                $reseller->balance -= $totalCost;
                $reseller->save();

                $transaction = new Transaction();
                $transaction->user_id = $reseller->id;
                $transaction->amount = $totalCost;
                $transaction->post_balance = $reseller->balance;
                $transaction->charge = 0;
                $transaction->trx_type = '-';
                $transaction->details = 'Created client @' . $username . ' for ' . $durationDays . ' days with ' . count($accountIds) . ' account(s)';
                $transaction->trx = getTrx();
                $transaction->remark = 'client_create';
                $transaction->save();
            }

            // Create Client User
            $user = new User();
            $user->firstname = $firstname;
            $user->lastname = $lastname;
            $user->email = $email;
            $user->username = $username;
            $user->password = Hash::make($request->password);
            $user->country_name = 'United States';
            $user->country_code = 'US';
            $user->dial_code = '1';
            $user->plan_id = 0;
            $user->account_ids = $accountIds;
            $user->account_prices = [];
            $user->expires_at = now()->addDays($durationDays);
            $user->is_reseller = 0;
            $user->reseller_id = $reseller->id;
            $user->is_tester = 0;
            $user->is_exclusive = 0;

            // Verified flags
            $user->ev = Status::VERIFIED;
            $user->sv = Status::VERIFIED;
            $user->kv = Status::KYC_VERIFIED;
            $user->tv = Status::DISABLE;
            $user->ts = Status::DISABLE;
            $user->status = Status::USER_ACTIVE;
            $user->profile_complete = 1;

            $user->save();

            DB::commit();
        } catch (\Exception $e) {
            DB::rollBack();
            $notify[] = ['error', 'Failed to create client user: ' . $e->getMessage()];
            return back()->withNotify($notify)->withInput();
        }

        $notify[] = ['success', 'Client user @' . $user->username . ' created successfully! Charged ' . showAmount($totalCost) . ' from wallet.'];
        return redirect()->route('reseller.users.index')->withNotify($notify);
    }

    public function editUser($id)
    {
        $reseller = auth()->user();
        $user = User::where('reseller_id', $reseller->id)->findOrFail($id);
        $pageTitle = 'Edit Client User - @' . $user->username;

        $accounts = AccountListing::with('socialMedia')
            ->active()
            ->where('cookie_status', '!=', 0)
            ->orderBy('social_media_id', 'asc')
            ->get();

        return view('reseller.users.edit', compact('pageTitle', 'reseller', 'user', 'accounts'));
    }

    public function updateUser(Request $request, $id)
    {
        $reseller = auth()->user();
        $user = User::where('reseller_id', $reseller->id)->findOrFail($id);

        $request->validate([
            'name'          => 'required|string|max:80',
            'password'      => 'nullable|string|min:4',
            'account_ids'   => 'required|array|min:1',
            'account_ids.*' => 'integer|exists:account_listings,id',
        ]);

        $name = trim($request->name);
        $nameParts = array_values(array_filter(explode(' ', $name)));
        $user->firstname = $nameParts[0] ?? $user->firstname;
        $user->lastname  = isset($nameParts[1]) ? implode(' ', array_slice($nameParts, 1)) : $user->lastname;

        if ($request->filled('password')) {
            $user->password = Hash::make($request->password);
        }

        $oldAccounts = (array) ($user->account_ids ?? []);
        $newAccounts = array_values(array_map('intval', $request->account_ids));

        // Detect newly added accounts
        $addedAccounts = array_diff($newAccounts, $oldAccounts);
        $resellerPrices = (array) ($reseller->account_prices ?? []);

        $extraCost = 0.00;
        if (!empty($addedAccounts)) {
            // Check remaining days on the user subscription
            $daysRemaining = $user->expires_at && $user->expires_at->isFuture()
                ? now()->diffInDays($user->expires_at)
                : 30;

            $monthsRatio = max(1, $daysRemaining) / 30;

            foreach ($addedAccounts as $addId) {
                $unitPrice = isset($resellerPrices[$addId]) ? (float) $resellerPrices[$addId] : 0.00;
                $extraCost += round($unitPrice * $monthsRatio, 2);
            }

            if ($extraCost > $reseller->balance) {
                $notify[] = ['error', 'Insufficient wallet balance to add these new accounts. Upgrade cost is ' . showAmount($extraCost) . ' but your balance is ' . showAmount($reseller->balance)];
                return back()->withNotify($notify);
            }
        }

        DB::beginTransaction();
        try {
            if ($extraCost > 0) {
                $reseller->balance -= $extraCost;
                $reseller->save();

                $transaction = new Transaction();
                $transaction->user_id = $reseller->id;
                $transaction->amount = $extraCost;
                $transaction->post_balance = $reseller->balance;
                $transaction->charge = 0;
                $transaction->trx_type = '-';
                $transaction->details = 'Added ' . count($addedAccounts) . ' account(s) to client @' . $user->username;
                $transaction->trx = getTrx();
                $transaction->remark = 'client_account_add';
                $transaction->save();
            }

            $user->account_ids = $newAccounts;
            $user->save();

            DB::commit();
        } catch (\Exception $e) {
            DB::rollBack();
            $notify[] = ['error', 'Failed to update user: ' . $e->getMessage()];
            return back()->withNotify($notify);
        }

        $msg = 'Client details updated successfully.';
        if ($extraCost > 0) {
            $msg .= ' Charged ' . showAmount($extraCost) . ' for newly added accounts.';
        }

        $notify[] = ['success', $msg];
        return redirect()->route('reseller.users.index')->withNotify($notify);
    }

    public function extendUser(Request $request, $id)
    {
        $reseller = auth()->user();
        $user = User::where('reseller_id', $reseller->id)->findOrFail($id);

        $request->validate([
            'duration_days' => 'required|integer|in:30,60,90,180,365',
        ]);

        $durationDays = (int) $request->duration_days;
        $months = $durationDays / 30;

        $accountIds = (array) ($user->account_ids ?? []);
        $resellerPrices = (array) ($reseller->account_prices ?? []);

        $monthlyCost = 0.00;
        foreach ($accountIds as $accId) {
            $monthlyCost += isset($resellerPrices[$accId]) ? (float) $resellerPrices[$accId] : 0.00;
        }

        $totalCost = round($monthlyCost * $months, 2);

        if ($totalCost > $reseller->balance) {
            $notify[] = ['error', 'Insufficient wallet balance to extend user. Extension cost is ' . showAmount($totalCost) . ' but your balance is ' . showAmount($reseller->balance) . '. Please recharge your wallet.'];
            return back()->withNotify($notify);
        }

        DB::beginTransaction();
        try {
            if ($totalCost > 0) {
                $reseller->balance -= $totalCost;
                $reseller->save();

                $transaction = new Transaction();
                $transaction->user_id = $reseller->id;
                $transaction->amount = $totalCost;
                $transaction->post_balance = $reseller->balance;
                $transaction->charge = 0;
                $transaction->trx_type = '-';
                $transaction->details = 'Extended client @' . $user->username . ' by ' . $durationDays . ' days';
                $transaction->trx = getTrx();
                $transaction->remark = 'client_extend';
                $transaction->save();
            }

            // Calculate new expiry date
            $baseDate = ($user->expires_at && $user->expires_at->isFuture()) ? $user->expires_at : now();
            $user->expires_at = $baseDate->copy()->addDays($durationDays);
            $user->save();

            DB::commit();
        } catch (\Exception $e) {
            DB::rollBack();
            $notify[] = ['error', 'Failed to extend client validity: ' . $e->getMessage()];
            return back()->withNotify($notify);
        }

        $notify[] = ['success', 'Client @' . $user->username . ' extended by ' . $durationDays . ' days until ' . showDateTime($user->expires_at, 'd M Y') . '! Charged ' . showAmount($totalCost) . ' from wallet.'];
        return back()->withNotify($notify);
    }

    public function statusUser($id)
    {
        $reseller = auth()->user();
        $user = User::where('reseller_id', $reseller->id)->findOrFail($id);

        if ($user->status == Status::USER_ACTIVE) {
            $user->status = Status::USER_BAN;
            $user->ban_reason = 'Banned by Reseller';
            $notify[] = ['success', 'Client user has been banned.'];
        } else {
            $user->status = Status::USER_ACTIVE;
            $user->ban_reason = null;
            $notify[] = ['success', 'Client user has been unbanned.'];
        }

        $user->save();
        return back()->withNotify($notify);
    }

    public function deleteUser($id)
    {
        $reseller = auth()->user();
        $user = User::where('reseller_id', $reseller->id)->findOrFail($id);
        $user->delete();

        $notify[] = ['success', 'Client user has been deleted.'];
        return back()->withNotify($notify);
    }

    public function logoutUser($id)
    {
        $reseller = auth()->user();
        $user = User::where('reseller_id', $reseller->id)->findOrFail($id);

        DB::table('sessions')->where('user_id', $user->id)->delete();
        $user->remember_token = null;
        $user->save();

        $notify[] = ['success', 'Client user has been logged out remotely.'];
        return back()->withNotify($notify);
    }

    public function transactions()
    {
        $pageTitle = 'Wallet Transactions';
        $reseller = auth()->user();

        $transactions = Transaction::where('user_id', $reseller->id)
            ->orderBy('id', 'desc')
            ->paginate(getPaginate());

        return view('reseller.transactions', compact('pageTitle', 'transactions', 'reseller'));
    }

    public function depositHistory()
    {
        $pageTitle = 'Deposit History';
        $reseller = auth()->user();

        $deposits = Deposit::where('user_id', $reseller->id)
            ->where('status', '!=', Status::PAYMENT_INITIATE)
            ->with('gateway')
            ->orderBy('id', 'desc')
            ->paginate(getPaginate());

        return view('reseller.deposit_history', compact('pageTitle', 'deposits', 'reseller'));
    }

    public function pricing()
    {
        $pageTitle = 'My Account Pricing';
        $reseller = auth()->user();

        $accounts = AccountListing::with('socialMedia')
            ->active()
            ->orderBy('social_media_id', 'asc')
            ->get();

        return view('reseller.pricing', compact('pageTitle', 'accounts', 'reseller'));
    }
}
