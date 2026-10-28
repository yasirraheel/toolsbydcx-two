<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Support\Facades\DB;

/**
 * Atomically reserves and refunds the credits assigned to an individual user.
 * A null limit intentionally means unlimited so existing TTS-enabled users are
 * not unexpectedly interrupted when the quota feature is introduced.
 */
class UserAudioCreditService
{
    public function remaining(User $user): ?int
    {
        if ($user->tts_credit_limit === null) {
            return null;
        }

        return max(0, (int) $user->tts_credit_limit - (int) $user->tts_credits_used);
    }

    public function reserve(User $user, int $amount): bool
    {
        $amount = max(0, $amount);
        if ($amount === 0) {
            return true;
        }

        return DB::transaction(function () use ($user, $amount) {
            $locked = User::query()->lockForUpdate()->find($user->id);
            if (!$locked) {
                return false;
            }

            if ($locked->tts_credit_limit !== null) {
                $remaining = max(0, (int) $locked->tts_credit_limit - (int) $locked->tts_credits_used);
                if ($remaining < $amount) {
                    return false;
                }
            }

            $locked->tts_credits_used = (int) $locked->tts_credits_used + $amount;
            $locked->saveQuietly();
            $user->setRawAttributes($locked->getAttributes(), true);

            return true;
        });
    }

    public function refund(User $user, int $amount): void
    {
        $amount = max(0, $amount);
        if ($amount === 0) {
            return;
        }

        DB::transaction(function () use ($user, $amount) {
            $locked = User::query()->lockForUpdate()->find($user->id);
            if (!$locked) {
                return;
            }

            $locked->tts_credits_used = max(0, (int) $locked->tts_credits_used - $amount);
            $locked->saveQuietly();
            $user->setRawAttributes($locked->getAttributes(), true);
        });
    }
}
