<?php

namespace App\Http\Controllers\Reseller;

use App\Constants\Status;
use App\Http\Controllers\Controller;
use App\Lib\FormProcessor;
use App\Models\AccountListing;
use App\Models\AdminNotification;
use App\Models\Deposit;
use App\Models\GatewayCurrency;
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
        $domain = $reseller->email_suffix ?: (parse_url(config('app.url') ?: url('/'), PHP_URL_HOST) ?: request()->getHost());

        $accounts = AccountListing::with('socialMedia')
            ->active()
            ->where('cookie_status', '!=', 0)
            ->orderBy('social_media_id', 'asc')
            ->get();

        return view('reseller.users.create', compact('pageTitle', 'reseller', 'domain', 'accounts'));
    }

    public function saveEmailSuffix(Request $request)
    {
        $request->validate([
            'email_suffix' => 'required|string|max:100',
        ]);

        $suffix = strtolower(trim($request->email_suffix));
        $suffix = ltrim($suffix, '@');
        $suffix = preg_replace('/[^a-z0-9.-]/i', '', $suffix);

        if (empty($suffix)) {
            return response()->json([
                'status'  => 'error',
                'message' => 'Please enter a valid domain suffix.',
            ], 422);
        }

        $reseller = auth()->user();
        $reseller->email_suffix = $suffix;
        $reseller->save();

        return response()->json([
            'status'  => 'success',
            'message' => 'Default email suffix "@' . $suffix . '" saved permanently.',
            'suffix'  => $suffix,
        ]);
    }

    public function storeUser(Request $request)
    {
        $reseller = auth()->user();
        $defaultHost = parse_url(config('app.url') ?: url('/'), PHP_URL_HOST) ?: request()->getHost();
        $domain = $reseller->email_suffix ?: $defaultHost;

        $request->validate([
            'name'          => 'required|string|max:80',
            'email_prefix'  => 'required|string|max:60',
            'email_suffix'  => 'nullable|string|max:100',
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

        $rawSuffix = trim($request->email_suffix ?: ($reseller->email_suffix ?: $domain));
        $rawSuffix = ltrim($rawSuffix, '@');
        $suffix = strtolower(preg_replace('/[^a-z0-9.-]/i', '', $rawSuffix));
        if (empty($suffix)) {
            $suffix = $domain;
        }

        if ($request->boolean('save_suffix_default') && $reseller->email_suffix !== $suffix) {
            $reseller->email_suffix = $suffix;
            $reseller->save();
        }

        $email = $prefix . '@' . $suffix;
        $username = $prefix;

        if (User::withTrashed()->where('email', $email)->orWhere('username', $username)->exists()) {
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
        } catch (\Illuminate\Database\UniqueConstraintViolationException $e) {
            DB::rollBack();
            $notify[] = ['error', 'The username/email "' . $prefix . '" is already registered. Please choose another prefix.'];
            return back()->withNotify($notify)->withInput();
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

    public function deposit()
    {
        $gatewayCurrency = GatewayCurrency::whereHas('method', function ($gate) {
            $gate->where('status', Status::ENABLE);
        })->with('method')->orderby('name')->get();
        $pageTitle = 'Recharge Wallet';
        return view('reseller.deposit', compact('gatewayCurrency', 'pageTitle'));
    }

    public function depositInsert(Request $request)
    {
        $request->validate([
            'amount' => 'required|numeric|gt:0',
            'gateway' => 'required',
            'currency' => 'required',
        ]);

        $user = auth()->user();

        $gate = GatewayCurrency::whereHas('method', function ($gate) {
            $gate->where('status', Status::ENABLE);
        })->where('method_code', $request->gateway)->where('currency', $request->currency)->first();
        if (!$gate) {
            $notify[] = ['error', 'Invalid gateway'];
            return back()->withNotify($notify);
        }

        if ($gate->min_amount > $request->amount || $gate->max_amount < $request->amount) {
            $notify[] = ['error', 'Please follow deposit limit'];
            return back()->withNotify($notify);
        }

        $charge = $gate->fixed_charge + ($request->amount * $gate->percent_charge / 100);
        $payable = $request->amount + $charge;
        $finalAmount = $payable * $gate->rate;

        $data = new Deposit();
        $data->user_id = $user->id;
        $data->account_listing_id = 0;
        $data->request_type = null;
        $data->method_code = $gate->method_code;
        $data->method_currency = strtoupper($gate->currency);
        $data->amount = $request->amount;
        $data->charge = $charge;
        $data->rate = $gate->rate;
        $data->final_amount = $finalAmount;
        $data->btc_amount = 0;
        $data->btc_wallet = "";
        $data->trx = getTrx();
        $data->success_url = urlPath('reseller.deposit.history');
        $data->failed_url = urlPath('reseller.deposit.history');
        $data->save();
        session()->put('Track', $data->trx);
        return to_route('reseller.deposit.confirm');
    }

    public function depositConfirm()
    {
        $track = session()->get('Track');
        $deposit = Deposit::where('trx', $track)->where('status', Status::PAYMENT_INITIATE)->orderBy('id', 'DESC')->with('gateway')->firstOrFail();

        if ($deposit->method_code >= 1000) {
            return to_route('reseller.deposit.manual.confirm');
        }

        $dirName = $deposit->gateway->alias;
        $new = 'App\\Http\\Controllers\\Gateway\\' . $dirName . '\\ProcessController';

        $data = $new::process($deposit);
        $data = json_decode($data);

        if (isset($data->error)) {
            $notify[] = ['error', $data->message];
            return to_route('reseller.deposit')->withNotify($notify);
        }
        if (isset($data->redirect)) {
            return redirect($data->redirect_url);
        }

        // for Stripe V3
        if (@$data->session) {
            $deposit->btc_wallet = $data->session->id;
            $deposit->save();
        }

        $pageTitle = 'Payment Confirm';
        return view($data->view, compact('data', 'pageTitle', 'deposit'));
    }

    public function manualDepositConfirm()
    {
        $track = session()->get('Track');
        $data = Deposit::with('gateway')->where('status', Status::PAYMENT_INITIATE)->where('trx', $track)->first();
        abort_if(!$data, 404);
        if ($data->method_code > 999) {
            $pageTitle = 'Confirm Deposit';
            $method = $data->gatewayCurrency();
            $gateway = $method->method;
            return view('reseller.manual_deposit', compact('data', 'pageTitle', 'method', 'gateway'));
        }
        abort(404);
    }

    public function manualDepositUpdate(Request $request)
    {
        $track = session()->get('Track');
        $data = Deposit::with('gateway')->where('status', Status::PAYMENT_INITIATE)->where('trx', $track)->first();
        abort_if(!$data, 404);
        $gatewayCurrency = $data->gatewayCurrency();
        $gateway = $gatewayCurrency->method;
        $formData = $gateway->form->form_data;

        $formProcessor = new FormProcessor();
        $validationRule = $formProcessor->valueValidation($formData);
        $request->validate($validationRule);
        $userData = $formProcessor->processFormData($request, $formData);

        $data->detail = $userData;
        $data->status = Status::PAYMENT_PENDING;
        $data->save();

        $adminNotification = new AdminNotification();
        $adminNotification->user_id = $data->user->id;
        $adminNotification->title = 'Deposit request from reseller ' . $data->user->username;
        $adminNotification->click_url = urlPath('admin.deposit.details', $data->id);
        $adminNotification->save();

        notify($data->user, 'DEPOSIT_REQUEST', [
            'method_name' => $data->gatewayCurrency()->name,
            'method_currency' => $data->method_currency,
            'method_amount' => showAmount($data->final_amount, currencyFormat: false),
            'amount' => showAmount($data->amount, currencyFormat: false),
            'charge' => showAmount($data->charge, currencyFormat: false),
            'rate' => showAmount($data->rate, currencyFormat: false),
            'trx' => $data->trx
        ]);

        $notify[] = ['success', 'Your deposit request has been submitted for approval.'];
        return to_route('reseller.deposit.history')->withNotify($notify);
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
