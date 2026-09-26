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
            'label' => 'nullable|string',
            'email' => 'required|email|unique:google_flow_accounts',
            'password' => 'required|string',
            'totp_secret' => 'nullable|string',
            'backup_codes' => 'nullable|string',
            'status' => 'required|in:active,disabled,locked',
            'assigned_to_user_id' => 'nullable|exists:users,id',
            'notes' => 'nullable|string',
        ]);

        $backupCodes = [];
        if ($request->backup_codes) {
            $codes = explode("\n", str_replace("\r", "", $request->backup_codes));
            $backupCodes = array_filter(array_map('trim', $codes));
        }

        GoogleFlowAccount::create([
            'label' => $request->label,
            'email' => $request->email,
            'password_encrypted' => Crypt::encryptString($request->password),
            'totp_secret_encrypted' => $request->totp_secret ? Crypt::encryptString($request->totp_secret) : null,
            'backup_codes' => empty($backupCodes) ? null : $backupCodes,
            'status' => $request->status,
            'assigned_to_user_id' => $request->assigned_to_user_id,
            'notes' => $request->notes,
        ]);

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
            'label' => 'nullable|string',
            'email' => 'required|email|unique:google_flow_accounts,email,' . $id,
            'password' => 'nullable|string',
            'totp_secret' => 'nullable|string',
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
            $data['totp_secret_encrypted'] = Crypt::encryptString($request->totp_secret);
        }

        if ($request->has('backup_codes')) {
            $backupCodes = [];
            if ($request->backup_codes) {
                $codes = explode("\n", str_replace("\r", "", $request->backup_codes));
                $backupCodes = array_filter(array_map('trim', $codes));
            }
            $data['backup_codes'] = empty($backupCodes) ? null : $backupCodes;
        }

        $account->update($data);

        $notify[] = ['success', 'Google account updated successfully'];
        return back()->withNotify($notify);
    }

    public function delete($id)
    {
        $account = GoogleFlowAccount::findOrFail($id);
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

        $pairing = ExtensionPairing::create([
            'user_id' => $request->user_id,
            'google_flow_account_id' => $request->google_flow_account_id,
            'pairing_code' => Str::random(12),
            'expires_at' => now()->addMinutes(15),
            'is_active' => true,
        ]);

        $notify[] = ['success', 'Pairing code generated: ' . $pairing->pairing_code];
        return back()->withNotify($notify);
    }

    public function revokeExtension($id)
    {
        $pairing = ExtensionPairing::findOrFail($id);
        $pairing->update(['is_active' => false]);
        
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
