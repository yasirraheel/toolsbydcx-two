<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ExtensionPairing;
use App\Models\FlowLoginAttempt;
use App\Models\GoogleFlowAccount;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Str;
use Carbon\Carbon;

class DcxFlowController extends Controller
{
    public function pair(Request $request)
    {
        $request->validate([
            'code' => 'required|string',
            'installationId' => 'required|string',
        ]);

        $pairing = ExtensionPairing::where('pairing_code', $request->code)
            ->where('is_active', true)
            ->where(function ($q) {
                $q->whereNull('expires_at')->orWhere('expires_at', '>', now());
            })->first();

        if (!$pairing) {
            return response()->json(['error' => 'Invalid or expired pairing code'], 400);
        }

        $pairing->access_token = Str::random(64);
        $pairing->uninstall_token = Str::random(32);
        $pairing->installation_id = $request->installationId;
        $pairing->pairing_code = null;
        $pairing->browser = $request->header('User-Agent');
        $pairing->save();

        return response()->json([
            'accessToken' => $pairing->access_token,
            'expiresAt' => $pairing->expires_at,
            'uninstallToken' => $pairing->uninstall_token,
        ]);
    }

    public function status(Request $request)
    {
        $user = $request->user();
        return response()->json([
            'status' => 'connected',
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
            ]
        ]);
    }

    public function start(Request $request)
    {
        $user = $request->user();
        $pairing = $request->attributes->get('extension_pairing');

        if (!$pairing || !$pairing->google_flow_account_id) {
            return response()->json(['error' => 'No assigned Google account'], 400);
        }

        $account = $pairing->googleFlowAccount;
        if ($account->status !== 'active') {
            return response()->json(['error' => 'Assigned account is not active'], 400);
        }

        $attempt = FlowLoginAttempt::create([
            'id' => Str::uuid(),
            'user_id' => $user->id,
            'google_flow_account_id' => $account->id,
            'status' => 'in_progress',
            'expires_at' => now()->addMinutes(10),
        ]);

        return response()->json([
            'attemptId' => $attempt->id,
            'email' => $account->email,
        ]);
    }

    public function step(Request $request)
    {
        $request->validate([
            'attemptId' => 'required|string',
            'stage' => 'required|string|in:email,password,otp,backup_code',
        ]);

        $attempt = FlowLoginAttempt::where('id', $request->attemptId)
            ->where('user_id', $request->user()->id)
            ->where('status', 'in_progress')
            ->first();

        if (!$attempt || $attempt->expires_at->isPast()) {
            return response()->json(['error' => 'Invalid or expired attempt'], 400);
        }

        $account = $attempt->googleFlowAccount;

        switch ($request->stage) {
            case 'email':
                return response()->json(['email' => $account->email]);
            case 'password':
                return response()->json(['password' => $account->password]);
            case 'otp':
                try {
                    $google2fa = new \PragmaRX\Google2FA\Google2FA();
                    $secret = Crypt::decryptString($account->totp_secret_encrypted);
                    $otp = $google2fa->getCurrentOtp($secret);
                    $attempt->increment('otp_attempt_count');
                    return response()->json(['otp' => $otp]);
                } catch (\Exception $e) {
                    return response()->json(['error' => 'Failed to generate OTP'], 500);
                }
            case 'backup_code':
                if (empty($account->backup_codes)) {
                    return response()->json(['error' => 'No backup codes available'], 400);
                }
                $codes = $account->backup_codes;
                $code = array_shift($codes);
                $account->backup_codes = $codes;
                $account->save();
                
                $attempt->update(['backup_code_used' => true]);
                
                return response()->json(['backup_code' => $code]);
        }

        return response()->json(['error' => 'Invalid stage'], 400);
    }

    public function finish(Request $request)
    {
        $request->validate([
            'attemptId' => 'required|string',
            'outcome' => 'required|string|in:success,cancelled,failed',
        ]);

        $attempt = FlowLoginAttempt::where('id', $request->attemptId)
            ->where('user_id', $request->user()->id)
            ->first();

        if (!$attempt) {
            return response()->json(['error' => 'Attempt not found'], 404);
        }

        $attempt->update([
            'status' => $request->outcome,
            'outcome' => $request->outcome,
        ]);

        return response()->json(['status' => 'recorded']);
    }

    public function disconnect(Request $request)
    {
        $pairing = $request->attributes->get('extension_pairing');
        if ($pairing) {
            $pairing->update([
                'is_active' => false,
                'access_token' => null,
            ]);
        }

        return response()->json(['status' => 'disconnected']);
    }
}
