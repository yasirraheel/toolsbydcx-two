<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ExtensionPairing;
use App\Models\FlowLoginAttempt;
use App\Models\GoogleFlowAccount;
use App\Services\FlowAccess;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use PragmaRX\Google2FA\Google2FA;

class DcxFlowController extends Controller
{
    public function pair(Request $request)
    {
        if (!$request->isJson()) {
            $payload = json_decode($request->getContent(), true);
            if (is_array($payload)) {
                $request->merge($payload);
            }
        }

        $request->validate(['code' => 'required|string|max:128', 'installationId' => 'required|string|max:64', 'codeVerifier' => 'nullable|string|min:43|max:128', 'expectedUserId' => 'nullable|integer']);
        return DB::transaction(function () use ($request) {
            $pairing = ExtensionPairing::where('pairing_code', $request->code)->where('is_active', true)->lockForUpdate()->first();
            if (!$pairing || !$pairing->expires_at || $pairing->isExpired()) {
                return response()->json(['message' => 'Invalid or expired pairing code.'], 422);
            }
            if ($pairing->code_challenge) {
                $challenge = rtrim(strtr(base64_encode(hash('sha256', (string) $request->codeVerifier, true)), '+/', '-_'), '=');
                if (!hash_equals($pairing->code_challenge, $challenge) || (int) $request->expectedUserId !== (int) $pairing->user_id) {
                    return response()->json(['message' => 'The connection proof does not match this browser.'], 422);
                }
            }
            $user = $pairing->user;
            $account = $pairing->googleFlowAccount;
            $isAssigned = $account && (((int) $user->google_flow_account_id === (int) $account->id) || ((int) $account->assigned_to_user_id === (int) $user->id));
            if (!FlowAccess::eligible($user) || !$account || $account->status !== 'active' || !$isAssigned) {
                return response()->json([
                    'message' => 'An active plan and assigned account are required.',
                    'reason' => 'plan_inactive'
                ], 403);
            }
            $token = Str::random(64);
            $pairing->update([
                'access_token' => hash('sha256', $token), 'uninstall_token' => hash('sha256', $uninstallToken = Str::random(48)),
                'installation_id' => $request->installationId, 'pairing_code' => null, 'code_challenge' => null,
                'expires_at' => $user->expires_at,
                'browser' => Str::limit($request->header('User-Agent', ''), 250, ''),
                'extension_version' => Str::limit($request->header('X-DCX-Flow-Version', ''), 50, ''),
            ]);
            return response()->json(['accessToken' => $token, 'expiresAt' => $pairing->expires_at->toIso8601String(),
                'uninstallToken' => $uninstallToken, 'userId' => $user->id])->header('Cache-Control', 'no-store, private');
        });
    }

    public function status(Request $request)
    {
        $user = $request->user();
        $account = $this->account($request);
        return response()->json([
            'connected' => true, 'userId' => $user->id,
            'user' => ['id' => $user->id, 'name' => trim($user->fullname) ?: $user->username,
                'plan' => $user->plan?->name, 'planLabel' => $user->plan?->name,
                'planExpiresAt' => $user->expires_at?->toIso8601String()],
            'assignedAccount' => ['email' => $account->email],
        ]);
    }

    private function account(Request $request): GoogleFlowAccount
    {
        $pairing = $request->attributes->get('extension_pairing');
        $account = $pairing ? $pairing->googleFlowAccount : null;
        $isAssigned = $account && (((int) $request->user()->google_flow_account_id === (int) $account->id) || ((int) $account->assigned_to_user_id === (int) $request->user()->id));
        if (!$account || $account->status !== 'active' || !$isAssigned) {
            abort(response()->json([
                'message' => 'No active Google account is assigned to this connection.',
                'reason' => 'account_inactive'
            ], 403));
        }
        return $account;
    }

    public function start(Request $request)
    {
        $account = $this->account($request);
        return DB::transaction(function () use ($request, $account) {
            $pairing = ExtensionPairing::whereKey($request->attributes->get('extension_pairing')->id)->lockForUpdate()->firstOrFail();
            abort_unless($pairing->is_active && $pairing->access_token, 401, 'Connection revoked.');
            FlowLoginAttempt::where('extension_pairing_id', $pairing->id)->where('status', 'in_progress')
                ->update(['status' => 'cancelled', 'outcome' => 'cancelled']);
            $attempt = FlowLoginAttempt::create([
                'id' => (string) Str::uuid(), 'user_id' => $request->user()->id,
                'google_flow_account_id' => $account->id, 'extension_pairing_id' => $pairing->id,
                'status' => 'in_progress', 'expires_at' => now()->addMinutes(10),
            ]);
            return response()->json(['attemptId' => $attempt->id, 'expiresAt' => $attempt->expires_at->toIso8601String()]);
        });
    }

    public function step(Request $request)
    {
        $request->validate(['attemptId' => 'required|uuid', 'stage' => 'required|in:email,password,otp,backup_code']);
        $this->account($request);
        return DB::transaction(function () use ($request) {
            $attempt = FlowLoginAttempt::whereKey($request->attemptId)->where('user_id', $request->user()->id)
                ->where('extension_pairing_id', $request->attributes->get('extension_pairing')->id)
                ->where('status', 'in_progress')->lockForUpdate()->first();
            abort_unless($attempt && $attempt->expires_at->isFuture(), 422, 'Invalid or expired attempt.');
            $account = GoogleFlowAccount::whereKey($attempt->google_flow_account_id)->lockForUpdate()->first();
            if (!$account || $account->status !== 'active' || (int) $account->assigned_to_user_id !== (int) $request->user()->id) {
                abort(response()->json(['message' => 'Account assignment changed.', 'reason' => 'account_inactive'], 403));
            }
            switch ($request->stage) {
                case 'email':
                    return response()->json(['value' => $account->email, 'attemptId' => $attempt->id]);
                case 'password':
                    return response()->json(['value' => $account->password, 'attemptId' => $attempt->id]);
                case 'otp':
                    abort_if($attempt->otp_attempt_count >= 2, 422, 'Automatic OTP limit reached. Use another verification method.');
                    abort_unless($account->totp_secret_encrypted, 422, 'No authenticator secret is configured.');
                    try {
                        $otp = (new Google2FA())->getCurrentOtp(Crypt::decryptString($account->totp_secret_encrypted));
                    } catch (\Exception $e) {
                        return response()->json(['message' => 'Authenticator configuration is invalid. Contact your administrator.'], 422);
                    }
                    // Near-expiry prefetches are discarded by the client without submitting.
                    if (30 - (time() % 30) >= 8) { $attempt->increment('otp_attempt_count'); }
                    return response()->json([
                        'value' => $otp,
                        'expiresAt' => now()->setTimestamp((intdiv(time(), 30) + 1) * 30)->toIso8601String(),
                        'attemptId' => $attempt->id,
                    ]);
                case 'backup_code':
                    abort_if($attempt->backup_code_used, 422, 'A backup code was already issued for this attempt.');
                    $codes = array_values($account->backup_codes ?? []);
                    abort_unless(count($codes), 422, 'No backup codes are available.');
                    $code = array_shift($codes);
                    $account->update(['backup_codes' => $codes]);
                    $attempt->update(['backup_code_used' => true]);
                    return response()->json(['value' => $code, 'attemptId' => $attempt->id]);
            }
        });
    }

    public function finish(Request $request)
    {
        $request->validate(['attemptId' => 'required|uuid', 'outcome' => 'required|in:success,cancelled,failed']);
        $attempt = FlowLoginAttempt::whereKey($request->attemptId)->where('user_id', $request->user()->id)
            ->where('extension_pairing_id', $request->attributes->get('extension_pairing')->id)->firstOrFail();
        abort_unless($attempt->expires_at->isFuture(), 422, 'Attempt expired.');
        FlowLoginAttempt::whereKey($attempt->id)->where('status', 'in_progress')
            ->update(['status' => $request->outcome, 'outcome' => $request->outcome]);
        return response()->json(['status' => 'recorded']);
    }

    public function uninstall(Request $request)
    {
        if ($request->filled('key')) {
            FlowAccess::revoke(ExtensionPairing::where('uninstall_token', hash('sha256', $request->key)));
        }
        return redirect()->away('https://accounts.google.com/Logout');
    }

    public function disconnect(Request $request)
    {
        FlowAccess::revoke(ExtensionPairing::whereKey($request->attributes->get('extension_pairing')->id));
        return response()->json(['status' => 'disconnected']);
    }
}
