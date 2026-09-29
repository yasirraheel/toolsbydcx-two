<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\GoogleFlowAccount;
use App\Models\ExtensionPairing;
use App\Models\FlowLoginAttempt;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Str;
use App\Services\FlowAccess;
use Illuminate\Support\Facades\DB;

class GoogleFlowController extends Controller
{
    public function index()
    {
        $pageTitle = 'Google Flow Accounts';
        $accounts = GoogleFlowAccount::with('user')->latest()->paginate(getPaginate());
        return view('admin.google_flow.index', compact('pageTitle', 'accounts'));
    }

    public function create()
    {
        $pageTitle = 'Add New Google Account';
        $users = User::active()->get();
        return view('admin.google_flow.create', compact('pageTitle', 'users'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'label' => 'nullable|string|max:255',
            'email' => 'required|email|max:255|unique:google_flow_accounts',
            'password' => 'required|string',
            'totp_secret' => ['nullable', 'string', 'regex:/^[A-Z2-7a-z\s]+$/'],
            'backup_codes' => 'nullable|string',
            'status' => 'required|in:active,disabled,locked',
            'assigned_to_user_id' => 'nullable|exists:users,id',
            'notes' => 'nullable|string',
        ]);

        $backupCodes = [];
        if ($request->backup_codes) {
            $codes = explode("\n", str_replace("\r", "", $request->backup_codes));
            $backupCodes = array_values(array_filter(array_map(fn($code) => preg_replace('/[\s-]+/', '', $code), $codes)));
            foreach ($backupCodes as $code) { if (!preg_match('/^\d{8}$/', $code)) { throw \Illuminate\Validation\ValidationException::withMessages(['backup_codes' => 'Enter unused 8-digit Google backup codes, one per line.']); } }
        }

        DB::transaction(function () use ($request, $backupCodes) {
        $account = GoogleFlowAccount::create([
            'label' => $request->label,
            'email' => $request->email,
            'password_encrypted' => Crypt::encryptString($request->password),
            'totp_secret_encrypted' => $request->totp_secret ? Crypt::encryptString(strtoupper(preg_replace('/\s+/', '', $request->totp_secret))) : null,
            'backup_codes' => empty($backupCodes) ? null : $backupCodes,
            'status' => $request->status,
            'assigned_to_user_id' => null,
            'notes' => $request->notes,
        ]);

        if ($request->assigned_to_user_id) { FlowAccess::assign(User::findOrFail($request->assigned_to_user_id), $account->id); }
        });
        $notify[] = ['success', 'Google account added successfully'];
        return redirect()->route('admin.google-flow.index')->withNotify($notify);
    }

    public function edit($id)
    {
        $account = GoogleFlowAccount::findOrFail($id);
        $pageTitle = 'Edit Google Account';
        $users = User::active()->get();
        return view('admin.google_flow.edit', compact('pageTitle', 'account', 'users'));
    }

    public function update(Request $request, $id)
    {
        $account = GoogleFlowAccount::findOrFail($id);
        
        $request->validate([
            'label' => 'nullable|string|max:255',
            'email' => 'required|email|max:255|unique:google_flow_accounts,email,' . $id,
            'password' => 'nullable|string',
            'totp_secret' => ['nullable', 'string', 'regex:/^[A-Z2-7a-z\s]+$/'],
            'backup_codes' => 'nullable|string',
            'status' => 'required|in:active,disabled,locked',
            'assigned_to_user_id' => 'nullable|exists:users,id',
            'notes' => 'nullable|string',
        ]);

        $data = [
            'label' => $request->label,
            'email' => $request->email,
            'status' => $request->status,
            'assigned_to_user_id' => $request->assigned_to_user_id,
            'notes' => $request->notes,
        ];

        if ($request->password) {
            $data['password_encrypted'] = Crypt::encryptString($request->password);
        }

        if ($request->filled('totp_secret')) {
            $data['totp_secret_encrypted'] = Crypt::encryptString(strtoupper(preg_replace('/\s+/', '', $request->totp_secret)));
        }

        if ($request->has('backup_codes')) {
            $backupCodes = [];
            if ($request->backup_codes) {
                $codes = explode("\n", str_replace("\r", "", $request->backup_codes));
                $backupCodes = array_values(array_filter(array_map(fn($code) => preg_replace('/[\s-]+/', '', $code), $codes)));
            foreach ($backupCodes as $code) { if (!preg_match('/^\d{8}$/', $code)) { throw \Illuminate\Validation\ValidationException::withMessages(['backup_codes' => 'Enter unused 8-digit Google backup codes, one per line.']); } }
            }
            $data['backup_codes'] = empty($backupCodes) ? null : $backupCodes;
        }

        DB::transaction(function () use ($account, $data) {
            if ($account->assigned_to_user_id != $data['assigned_to_user_id'] || $data['status'] !== 'active' || isset($data['password_encrypted']) || isset($data['totp_secret_encrypted']) || $account->email !== $data['email']) {
                FlowAccess::revoke(ExtensionPairing::where('google_flow_account_id', $account->id));
            }
            $target = $data['assigned_to_user_id'];
            unset($data['assigned_to_user_id']);
            $account->update($data);
            if ($account->assigned_to_user_id != $target) {
                $account->update(['assigned_to_user_id' => null]);
                if ($target) { FlowAccess::assign(User::findOrFail($target), $account->id); }
            }
        });

        $notify[] = ['success', 'Google account updated successfully'];
        return back()->withNotify($notify);
    }

    public function delete($id)
    {
        $account = GoogleFlowAccount::findOrFail($id);
        FlowAccess::revoke(ExtensionPairing::where('google_flow_account_id', $account->id));
        $account->delete();
        
        $notify[] = ['success', 'Google account deleted successfully'];
        return back()->withNotify($notify);
    }

    public function generatePairingCode(Request $request)
    {
        $request->validate([
            'user_id' => 'required|exists:users,id',
            'google_flow_account_id' => 'required|exists:google_flow_accounts,id',
        ]);

        $user = User::findOrFail($request->user_id);
        $account = GoogleFlowAccount::findOrFail($request->google_flow_account_id);
        if ($account->assigned_to_user_id != $user->id) {
            FlowAccess::assign($user, $account->id);
        }
        $pairing = FlowAccess::issue($user);

        $notify[] = ['success', 'Pairing code generated: ' . $pairing->pairing_code];
        return back()
            ->withNotify($notify)
            ->with('flow_pairing_code', $pairing->pairing_code)
            ->with('flow_pairing_user', $user->username ?: $user->email)
            ->with('flow_pairing_account', $account->label ?: $account->email);
    }

    public function assignAccount(Request $request, $id)
    {
        $request->validate(['google_flow_account_id' => 'nullable|integer|exists:google_flow_accounts,id']);
        FlowAccess::assign(User::findOrFail($id), $request->filled('google_flow_account_id') ? (int) $request->google_flow_account_id : null);
        return back()->withNotify([['success', 'Google Flow assignment saved. Generate a new code to connect.']]);
    }

    public function revokeExtension($id)
    {
        $pairing = ExtensionPairing::findOrFail($id);
        FlowAccess::revoke(ExtensionPairing::whereKey($pairing->id));
        
        $notify[] = ['success', 'Extension access revoked'];
        return back()->withNotify($notify);
    }

    public function pairings()
    {
        $pageTitle = 'Extension Pairings';
        $pairings = ExtensionPairing::with(['user', 'googleFlowAccount'])->latest()->paginate(getPaginate());
        return view('admin.google_flow.pairings', compact('pageTitle', 'pairings'));
    }

    public function loginAttempts()
    {
        $pageTitle = 'Flow Login Attempts';
        $attempts = FlowLoginAttempt::with(['user', 'googleFlowAccount'])->latest()->paginate(getPaginate());
        return view('admin.google_flow.login_attempts', compact('pageTitle', 'attempts'));
    }
}
