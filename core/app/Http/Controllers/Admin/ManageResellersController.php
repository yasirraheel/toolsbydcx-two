<?php

namespace App\Http\Controllers\Admin;

use App\Constants\Status;
use App\Http\Controllers\Controller;
use App\Models\AccountListing;
use App\Models\Transaction;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class ManageResellersController extends Controller
{
    public function all()
    {
        $pageTitle = 'All Resellers';
        $resellers = $this->resellerData();
        return view('admin.resellers.list', compact('pageTitle', 'resellers'));
    }

    public function active()
    {
        $pageTitle = 'Active Resellers';
        $resellers = $this->resellerData('active');
        return view('admin.resellers.list', compact('pageTitle', 'resellers'));
    }

    public function expired()
    {
        $pageTitle = 'Expired Resellers';
        $resellers = $this->resellerData('expired');
        return view('admin.resellers.list', compact('pageTitle', 'resellers'));
    }

    public function banned()
    {
        $pageTitle = 'Banned Resellers';
        $resellers = $this->resellerData('banned');
        return view('admin.resellers.list', compact('pageTitle', 'resellers'));
    }

    protected function resellerData($scope = null)
    {
        $query = User::resellers()->withCount('resellerUsers');

        if ($scope == 'active') {
            $query->where('status', Status::USER_ACTIVE)
                ->where(function ($q) {
                    $q->whereNull('expires_at')->orWhere('expires_at', '>=', now());
                });
        } elseif ($scope == 'expired') {
            $query->whereNotNull('expires_at')->where('expires_at', '<', now());
        } elseif ($scope == 'banned') {
            $query->where('status', Status::USER_BAN);
        }

        if (request()->search) {
            $search = request()->search;
            $query->where(function ($q) use ($search) {
                $q->where('username', 'like', "%$search%")
                    ->orWhere('email', 'like', "%$search%")
                    ->orWhere('firstname', 'like', "%$search%")
                    ->orWhere('lastname', 'like', "%$search%");
            });
        }

        return $query->orderBy('id', 'desc')->paginate(getPaginate());
    }

    public function create()
    {
        $pageTitle = 'Create New Reseller';
        $domain = parse_url(config('app.url') ?: url('/'), PHP_URL_HOST) ?: request()->getHost();
        $accounts = AccountListing::with('socialMedia')->active()->orderBy('social_media_id', 'asc')->get();

        return view('admin.resellers.create', compact('pageTitle', 'domain', 'accounts'));
    }

    public function store(Request $request)
    {
        $domain = parse_url(config('app.url') ?: url('/'), PHP_URL_HOST) ?: request()->getHost();

        $request->validate([
            'name'           => 'required|string|max:80',
            'email_prefix'   => 'required|string|max:60',
            'password'       => 'nullable|string|min:4',
            'expires_at'     => 'nullable|date',
            'initial_balance'=> 'nullable|numeric|min:0',
            'prices'         => 'nullable|array',
            'prices.*'       => 'nullable|numeric|min:0',
        ]);

        $name = trim($request->name);
        $nameParts = array_values(array_filter(explode(' ', $name)));
        $firstname = $nameParts[0] ?? 'Reseller';
        $lastname  = isset($nameParts[1]) ? implode(' ', array_slice($nameParts, 1)) : 'Partner';

        $prefix = strtolower(preg_replace('/[^a-z0-9._-]/i', '', trim($request->email_prefix)));
        if (empty($prefix)) {
            $prefix = 'reseller_' . Str::lower(Str::random(5));
        }

        $email = $prefix . '@' . $domain;
        $username = $prefix;

        if (User::withTrashed()->where('email', $email)->orWhere('username', $username)->exists()) {
            $notify[] = ['error', 'The username/email "' . $prefix . '" is already registered.'];
            return back()->withNotify($notify)->withInput();
        }

        $password = $request->password ?: Str::random(10);

        // Sanitize Account Prices (map of account_listing_id => price)
        $accountPrices = [];
        if ($request->prices && is_array($request->prices)) {
            foreach ($request->prices as $accId => $price) {
                if ($price !== null && $price !== '') {
                    $accountPrices[(int) $accId] = round((float) $price, 2);
                }
            }
        }

        try {
            $reseller = new User();
            $reseller->firstname = $firstname;
            $reseller->lastname = $lastname;
            $reseller->email = $email;
            $reseller->username = $username;
            $reseller->password = Hash::make($password);
            $reseller->country_name = 'United States';
            $reseller->country_code = 'US';
            $reseller->dial_code = '1';
            $reseller->plan_id = 0;
            $reseller->account_ids = [];
            $reseller->account_prices = $accountPrices;
            $reseller->expires_at = $request->expires_at ? Carbon::parse($request->expires_at) : now()->addYear();
            $reseller->is_reseller = 1;
            $reseller->is_tester = 0;
            $reseller->is_exclusive = 1; // Allow reseller to copy cookies by default if needed
            $reseller->balance = $request->initial_balance ? (float) $request->initial_balance : 0.00;

            // Active profile & verified flags
            $reseller->ev = Status::VERIFIED;
            $reseller->sv = Status::VERIFIED;
            $reseller->kv = Status::KYC_VERIFIED;
            $reseller->tv = Status::DISABLE;
            $reseller->ts = Status::DISABLE;
            $reseller->status = Status::USER_ACTIVE;
            $reseller->profile_complete = 1;

            $reseller->save();

            if ($request->initial_balance && (float) $request->initial_balance > 0) {
                $transaction = new Transaction();
                $transaction->user_id = $reseller->id;
                $transaction->amount = (float) $request->initial_balance;
                $transaction->post_balance = $reseller->balance;
                $transaction->charge = 0;
                $transaction->trx_type = '+';
                $transaction->details = 'Initial wallet balance assigned by Administrator';
                $transaction->trx = getTrx();
                $transaction->remark = 'admin_credit';
                $transaction->save();
            }

            $notify[] = ['success', 'Reseller account "' . $reseller->username . '" created successfully with password: ' . $password];
            return redirect()->route('admin.resellers.detail', $reseller->id)->withNotify($notify);
        } catch (\Illuminate\Database\UniqueConstraintViolationException $e) {
            $notify[] = ['error', 'The username/email "' . $prefix . '" is already registered. Please choose another prefix.'];
            return back()->withNotify($notify)->withInput();
        } catch (\Exception $e) {
            $notify[] = ['error', 'Failed to create reseller: ' . $e->getMessage()];
            return back()->withNotify($notify)->withInput();
        }
    }

    public function detail($id)
    {
        $reseller = User::resellers()->findOrFail($id);
        $pageTitle = 'Reseller Detail - ' . $reseller->username;
        $domain = parse_url(config('app.url') ?: url('/'), PHP_URL_HOST) ?: request()->getHost();

        $accounts = AccountListing::with('socialMedia')->active()->orderBy('social_media_id', 'asc')->get();
        $clientUsers = User::where('reseller_id', $reseller->id)->orderBy('id', 'desc')->paginate(15, ['*'], 'clients_page');
        $transactions = Transaction::where('user_id', $reseller->id)->orderBy('id', 'desc')->paginate(15, ['*'], 'trx_page');

        return view('admin.resellers.detail', compact('pageTitle', 'reseller', 'domain', 'accounts', 'clientUsers', 'transactions'));
    }

    public function update(Request $request, $id)
    {
        $reseller = User::resellers()->findOrFail($id);

        $request->validate([
            'name'       => 'required|string|max:80',
            'email'      => 'required|string|max:80',
            'password'   => 'nullable|string|min:4',
            'expires_at' => 'nullable|date',
            'prices'     => 'nullable|array',
            'prices.*'   => 'nullable|numeric|min:0',
        ]);

        $name = trim($request->name);
        $nameParts = array_values(array_filter(explode(' ', $name)));
        $reseller->firstname = $nameParts[0] ?? $reseller->firstname;
        $reseller->lastname  = isset($nameParts[1]) ? implode(' ', array_slice($nameParts, 1)) : $reseller->lastname;

        $domain = parse_url(config('app.url') ?: url('/'), PHP_URL_HOST) ?: request()->getHost();
        $rawEmail = trim($request->email);
        if (!str_contains($rawEmail, '@')) {
            $rawEmail = strtolower(preg_replace('/[^a-z0-9._-]/i', '', $rawEmail)) . '@' . $domain;
        }

        if ($rawEmail !== $reseller->email && User::where('email', $rawEmail)->where('id', '!=', $reseller->id)->exists()) {
            $notify[] = ['error', 'The email address is already taken by another account.'];
            return back()->withNotify($notify);
        }
        $reseller->email = $rawEmail;

        if ($request->filled('password')) {
            $reseller->password = Hash::make($request->password);
        }

        if ($request->filled('expires_at')) {
            $reseller->expires_at = Carbon::parse($request->expires_at);
        }

        // Update Account Prices
        if ($request->has('prices_submitted')) {
            $accountPrices = [];
            if ($request->prices && is_array($request->prices)) {
                foreach ($request->prices as $accId => $price) {
                    if ($price !== null && $price !== '') {
                        $accountPrices[(int) $accId] = round((float) $price, 2);
                    }
                }
            }
            $reseller->account_prices = $accountPrices;
        }

        $reseller->save();

        $notify[] = ['success', 'Reseller profile and account pricing updated successfully.'];
        return back()->withNotify($notify);
    }

    public function status(Request $request, $id)
    {
        $reseller = User::resellers()->findOrFail($id);

        if ($reseller->status == Status::USER_ACTIVE) {
            $request->validate([
                'ban_reason' => 'required|string|max:255'
            ]);
            $reseller->status = Status::USER_BAN;
            $reseller->ban_reason = $request->ban_reason;
            $notify[] = ['success', 'Reseller has been banned successfully'];
        } else {
            $reseller->status = Status::USER_ACTIVE;
            $reseller->ban_reason = null;
            $notify[] = ['success', 'Reseller has been unbanned successfully'];
        }

        $reseller->save();
        return back()->withNotify($notify);
    }

    public function addSubBalance(Request $request, $id)
    {
        $request->validate([
            'amount' => 'required|numeric|gt:0',
            'act'    => 'required|in:add,sub',
            'remark' => 'required|string|max:255',
        ]);

        $reseller = User::resellers()->findOrFail($id);
        $amount = (float) $request->amount;
        $trx = getTrx();

        $transaction = new Transaction();

        if ($request->act == 'add') {
            $reseller->balance += $amount;
            $transaction->trx_type = '+';
            $transaction->remark = 'balance_add';
            $notifyTemplate = 'BAL_ADD';
            $notify[] = ['success', showAmount($amount) . ' added to reseller wallet successfully'];
        } else {
            if ($amount > $reseller->balance) {
                $notify[] = ['error', 'Reseller does not have sufficient wallet balance to subtract this amount.'];
                return back()->withNotify($notify);
            }

            $reseller->balance -= $amount;
            $transaction->trx_type = '-';
            $transaction->remark = 'balance_subtract';
            $notifyTemplate = 'BAL_SUB';
            $notify[] = ['success', showAmount($amount) . ' subtracted from reseller wallet successfully'];
        }

        $reseller->save();

        $transaction->user_id = $reseller->id;
        $transaction->amount = $amount;
        $transaction->post_balance = $reseller->balance;
        $transaction->charge = 0;
        $transaction->trx = $trx;
        $transaction->details = $request->remark;
        $transaction->save();

        notify($reseller, $notifyTemplate, [
            'trx'          => $trx,
            'amount'       => showAmount($amount),
            'remark'       => $request->remark,
            'post_balance' => showAmount($reseller->balance)
        ]);

        return back()->withNotify($notify);
    }

    public function login($id)
    {
        $reseller = User::resellers()->findOrFail($id);
        Auth::loginUsingId($reseller->id);
        return redirect()->route('reseller.dashboard');
    }

    public function delete($id)
    {
        $reseller = User::resellers()->findOrFail($id);
        $reseller->delete();

        $notify[] = ['success', 'Reseller deleted successfully.'];
        return back()->withNotify($notify);
    }

    public function deleteBulk(Request $request)
    {
        $request->validate([
            'ids'   => 'required|array',
            'ids.*' => 'integer|exists:users,id',
        ]);

        $resellers = User::resellers()->whereIn('id', $request->ids)->get();
        $count = 0;
        foreach ($resellers as $reseller) {
            $reseller->delete();
            $count++;
        }
        $notify[] = ['success', $count . ' reseller(s) deleted successfully.'];
        return back()->withNotify($notify);
    }
}
