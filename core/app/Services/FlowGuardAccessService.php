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
        return $this->hasActivePlan($user)
            && $access?->enabled === true
            && (!$access->expires_at || $access->expires_at->isFuture());
    }

    /**
     * Flow Guard is tied to the user's paid panel plan. A direct Flow
     * account assignment must not keep a Google session alive after the plan
     * is missing, disabled, or expired.
     */
    public function hasActivePlan(User $user): bool
    {
        $user->loadMissing('plan');
        return (int) $user->plan_id > 0
            && $user->plan !== null
            && (int) $user->status === Status::USER_ACTIVE
            && $user->expires_at?->isFuture()
            && (int) ($user->plan->status ?? Status::DISABLE) === Status::ENABLE;
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

        $session = FlowGuardSession::with('user.plan')
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

            $session->loadMissing('user.plan');
            abort_unless($this->hasActivePlan($session->user), 403, 'Your ToolsByDcx plan is missing or expired.');

            $access = FlowGuardAccess::where('user_id', $userId)->lockForUpdate()->first();
            abort_unless($access && $access->enabled && (!$access->expires_at || $access->expires_at->isFuture()), 403, 'Flow Guard access is disabled.');

            $allowedModels = $this->allowedModels($access);
            $requestedModel = trim((string) ($metadata['model'] ?? ''));
            if (!$allowedModels) {
                abort(403, 'No Google Flow model is assigned to this account.');
            }
            $requestedNormalized = $this->modelKey($requestedModel);
            if ($requestedNormalized === '' || !collect($allowedModels)->contains(fn ($model) => $this->modelKey($model) === $requestedNormalized)) {
                abort(403, 'This model is not enabled for your Flow Guard account.');
            }

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
        $session->loadMissing('user.plan');
        $user = $session->user;
        $access = $this->access($session->user);
        $plan = $user->plan;
        $hasPlan = (int) $user->plan_id > 0 && $plan !== null;
        $planActive = $this->hasActivePlan($user);
        return [
            'authenticated' => true,
            'user' => [
                'id' => $user->id,
                'name' => $user->fullname ?: $user->username,
                'email' => $user->email,
                // Flow Guard must only keep a Google session alive for a
                // user with a currently active panel plan. These fields are
                // deliberately separate from the legacy Flow access toggle.
                'has_plan' => $hasPlan,
                'plan_active' => $planActive,
                'plan_id' => $hasPlan ? (int) $user->plan_id : null,
                'plan_name' => $plan?->name,
                'plan_expires_at' => $user->expires_at?->toIso8601String(),
            ],
            'access' => [
                'enabled' => (bool) $access?->enabled,
                'full_control' => (bool) $access?->full_control,
                'expires_at' => $access?->expires_at?->toIso8601String(),
                'credit_limit' => $access?->credit_limit,
                'credits_used' => (int) ($access?->credits_used ?? 0),
                'remaining_credits' => $access?->remainingCredits(),
                'generation_cost' => (int) ($access?->generation_cost ?? 0),
                'allowed_model' => $this->allowedModels($access)[0] ?? null,
                'allowed_models' => $this->allowedModels($access),
            ],
            'session_expires_at' => $session->expires_at?->toIso8601String(),
        ];
    }

    public function normalizeModelName(?string $model): string
    {
        $normalized = (string) $model;
        $normalized = (string) preg_replace('/\s+\[(?:lower|higher)\s+priority\]\s*$/iu', '', $normalized);
        return trim((string) preg_replace('/\s+/', ' ', $normalized));
    }

    public function modelKey(?string $model): string
    {
        $normalized = $this->normalizeModelName($model);
        $normalized = (string) preg_replace('/[\x{2010}-\x{2015}\x{2212}]/u', '-', $normalized);
        $normalized = (string) preg_replace('/\s*-\s*/u', ' - ', $normalized);
        return mb_strtolower(trim((string) preg_replace('/\s+/u', ' ', $normalized)));
    }

    public function allowedModels(?FlowGuardAccess $access): array
    {
        if (!$access) return [];
        $models = is_array($access->allowed_models) ? $access->allowed_models : [];
        if (!$models && filled($access->allowed_model)) {
            $models = [(string) $access->allowed_model];
        }

        return collect($models)
            ->map(fn ($model) => $this->normalizeModelName((string) $model))
            ->filter()
            ->unique()
            ->values()
            ->all();
    }
}
