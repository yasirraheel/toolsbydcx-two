<?php

namespace App\Services;

use App\Constants\Status;
use App\Models\FlowGuardAccess;
use App\Models\FlowGuardCreditReservation;
use App\Models\FlowGuardSession;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * Isolated authentication and quota service for the separate Flow Guard
 * extension. It intentionally does not read or mutate the legacy Flow
 * extension pairing/session tables.
 */
class FlowGuardAccessService
{
    public function access(User $user): ?FlowGuardAccess
    {
        return FlowGuardAccess::where('user_id', $user->id)->first();
    }

    public function eligible(User $user): bool
    {
        $access = $this->access($user);
        return (int) $user->status === Status::USER_ACTIVE
            && $access?->enabled === true
            && (!$access->expires_at || $access->expires_at->isFuture());
    }

    public function canAuthenticate(User $user): bool
    {
        // Server authentication is independent from Flow Guard entitlement.
        // Credits and permissions are enforced when Flow is actually used.
        return (int) $user->status === Status::USER_ACTIVE;
    }

    public function issue(User $user, Request $request, ?string $installationId = null): array
    {
        abort_unless($this->canAuthenticate($user), 403, 'This ToolsByDcx account is not active.');

        $rawToken = 'fg_' . Str::random(72);
        $session = FlowGuardSession::create([
            'user_id' => $user->id,
            'token_hash' => hash('sha256', $rawToken),
            'installation_id' => $installationId ?: $request->input('installationId'),
            'user_agent' => Str::limit((string) $request->userAgent(), 512, ''),
            'ip_address' => $request->ip(),
            'expires_at' => now()->addDays(30),
            'last_seen_at' => now(),
        ]);

        return [$session, $rawToken];
    }

    public function authenticate(Request $request): FlowGuardSession
    {
        $header = (string) $request->bearerToken();
        abort_if(!str_starts_with($header, 'fg_'), 401, 'Flow Guard authentication is required.');

        $session = FlowGuardSession::with('user')
            ->where('token_hash', hash('sha256', $header))
            ->first();

        abort_unless($session && $session->isActive() && $this->canAuthenticate($session->user), 401, 'Flow Guard session expired or disabled.');

        if (!$session->last_seen_at || $session->last_seen_at->lt(now()->subMinute())) {
            $session->forceFill(['last_seen_at' => now()])->saveQuietly();
        }

        return $session;
    }

    public function revoke(FlowGuardSession $session): void
    {
        $session->forceFill(['revoked_at' => now()])->saveQuietly();
    }

    public function releaseExpiredReservations(int $userId): void
    {
        DB::transaction(function () use ($userId) {
            $access = FlowGuardAccess::where('user_id', $userId)->lockForUpdate()->first();
            if (!$access) return;

            $expired = FlowGuardCreditReservation::where('user_id', $userId)
                ->where('status', 'reserved')
                ->where('expires_at', '<=', now())
                ->lockForUpdate()->get();

            if ($expired->isEmpty()) return;
            $amount = (int) $expired->sum('amount');
            FlowGuardCreditReservation::whereIn('id', $expired->pluck('id'))
                ->update(['status' => 'refunded', 'settled_at' => now(), 'updated_at' => now()]);
            $access->credits_used = max(0, (int) $access->credits_used - $amount);
            $access->saveQuietly();
        });
    }

    public function reserve(FlowGuardSession $session, ?int $requestedAmount = null, ?string $idempotencyKey = null, array $metadata = []): FlowGuardCreditReservation
    {
        $userId = (int) $session->user_id;
        $this->releaseExpiredReservations($userId);

        return DB::transaction(function () use ($session, $requestedAmount, $idempotencyKey, $metadata, $userId) {
            if ($idempotencyKey) {
                $existing = FlowGuardCreditReservation::where('session_id', $session->id)
                    ->where('idempotency_key', $idempotencyKey)->first();
                if ($existing) return $existing;
            }

            $access = FlowGuardAccess::where('user_id', $userId)->lockForUpdate()->first();
            abort_unless($access && $access->enabled && (!$access->expires_at || $access->expires_at->isFuture()), 403, 'Flow Guard access is disabled.');

            // The server-controlled configured cost is authoritative. The
            // client may request a preview amount, but cannot lower the cost.
            $amount = max((int) $access->generation_cost, (int) ($requestedAmount ?: 0));
            $remaining = $access->remainingCredits();
            abort_if($remaining !== null && $remaining < $amount, 402, 'Not enough Flow Guard credits.');

            $reservation = FlowGuardCreditReservation::create([
                'id' => (string) Str::uuid(),
                'user_id' => $userId,
                'session_id' => $session->id,
                'amount' => $amount,
                'status' => 'reserved',
                'idempotency_key' => $idempotencyKey,
                'metadata' => $metadata,
                'expires_at' => now()->addMinutes(10),
            ]);

            $access->credits_used = (int) $access->credits_used + $amount;
            $access->saveQuietly();
            return $reservation;
        });
    }

    public function settle(FlowGuardSession $session, string $id, string $status): FlowGuardCreditReservation
    {
        abort_unless(in_array($status, ['committed', 'refunded'], true), 422, 'Invalid reservation status.');

        return DB::transaction(function () use ($session, $id, $status) {
            $reservation = FlowGuardCreditReservation::whereKey($id)
                ->where('session_id', $session->id)->lockForUpdate()->firstOrFail();
            if ($reservation->status !== 'reserved') return $reservation;

            if ($status === 'refunded') {
                $access = FlowGuardAccess::where('user_id', $reservation->user_id)->lockForUpdate()->first();
                if ($access) {
                    $access->credits_used = max(0, (int) $access->credits_used - (int) $reservation->amount);
                    $access->saveQuietly();
                }
            }

            $reservation->forceFill(['status' => $status, 'settled_at' => now()])->saveQuietly();
            return $reservation->fresh();
        });
    }

    public function payload(FlowGuardSession $session): array
    {
        $access = $this->access($session->user);
        return [
            'authenticated' => true,
            'user' => [
                'id' => $session->user->id,
                'name' => $session->user->fullname ?: $session->user->username,
                'email' => $session->user->email,
            ],
            'access' => [
                'enabled' => (bool) $access?->enabled,
                'expires_at' => $access?->expires_at?->toIso8601String(),
                'credit_limit' => $access?->credit_limit,
                'credits_used' => (int) ($access?->credits_used ?? 0),
                'remaining_credits' => $access?->remainingCredits(),
                'generation_cost' => (int) ($access?->generation_cost ?? 0),
            ],
            'session_expires_at' => $session->expires_at?->toIso8601String(),
        ];
    }
}
