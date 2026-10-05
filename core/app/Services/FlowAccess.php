<?php

namespace App\Services;

use App\Models\ExtensionPairing;
use App\Models\FlowLoginAttempt;
use App\Models\GoogleFlowAccount;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class FlowAccess
{
    public static function eligible(?User $user): bool
    {
        return $user && $user->status == 1 && $user->ev == 1 && $user->sv == 1
            && $user->expires_at && $user->expires_at->isFuture();
    }

    public static function revoke($query): void
    {
        DB::transaction(function () use ($query) {
            $ids = (clone $query)->pluck('id');
            $query->update(['is_active' => false, 'access_token' => null, 'pairing_code' => null, 'uninstall_token' => null]);
            FlowLoginAttempt::whereIn('extension_pairing_id', $ids)->where('status', 'in_progress')
                ->update(['status' => 'cancelled', 'outcome' => 'cancelled']);
        });
    }

    public static function issue(User $user, ?string $challenge = null): ExtensionPairing
    {
        return DB::transaction(function () use ($user, $challenge) {
            $user = User::whereKey($user->id)->lockForUpdate()->firstOrFail();
            if (!self::eligible($user)) {
                throw ValidationException::withMessages(['user' => 'An active, verified user plan is required.']);
            }
            $account = GoogleFlowAccount::active()->where('assigned_to_user_id', $user->id)->lockForUpdate()->first();
            if (!$account) {
                throw ValidationException::withMessages(['account' => 'Ask an administrator to assign an active Google Flow account first.']);
            }
            self::revoke(ExtensionPairing::where('user_id', $user->id)->whereNotNull('pairing_code'));
            do {
                $code = $challenge ? \Illuminate\Support\Str::random(48) : (string) random_int(100000, 999999);
            } while (ExtensionPairing::where('pairing_code', $code)->exists());
            return ExtensionPairing::create([
                'user_id' => $user->id, 'google_flow_account_id' => $account->id,
                'pairing_code' => $code, 'code_challenge' => $challenge, 'expires_at' => now()->addMinutes(15), 'is_active' => true,
            ]);
        });
    }

    public static function assign(User $user, ?int $accountId, bool $force = false): void
    {
        DB::transaction(function () use ($user, $accountId, $force) {
            User::whereKey($user->id)->lockForUpdate()->firstOrFail();
            $account = $accountId ? GoogleFlowAccount::whereKey($accountId)->lockForUpdate()->firstOrFail() : null;
            if ($account && $account->status !== 'active') {
                if ($force) {
                    $account->status = 'active';
                    $account->save();
                } else {
                    throw ValidationException::withMessages(['google_flow_account_id' => 'Choose an active, unassigned account.']);
                }
            }
            if ($account && $account->assigned_to_user_id && $account->assigned_to_user_id != $user->id) {
                if ($force) {
                    self::revoke(ExtensionPairing::where('user_id', $account->assigned_to_user_id));
                } else {
                    throw ValidationException::withMessages(['google_flow_account_id' => 'Choose an active, unassigned account.']);
                }
            }
            self::revoke(ExtensionPairing::where('user_id', $user->id));
            GoogleFlowAccount::where('assigned_to_user_id', $user->id)->update(['assigned_to_user_id' => null]);
            if ($account) {
                $account->update(['assigned_to_user_id' => $user->id]);
            }
        });
    }
}
