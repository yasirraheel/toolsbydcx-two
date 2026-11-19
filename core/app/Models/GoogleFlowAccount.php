<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Crypt;

class GoogleFlowAccount extends Model
{
    protected $fillable = [
        'label',
        'email',
        'password_encrypted',
        'totp_secret_encrypted',
        'backup_codes',
        'status',
        'assigned_to_user_id',
        'active_sessions',
        'notes',
    ];

    protected $hidden = [
        'password_encrypted',
        'totp_secret_encrypted',
        'backup_codes',
    ];

    protected $casts = [
        'backup_codes' => 'array',
    ];

    public function setBackupCodesAttribute($codes)
    {
        if ($codes === null) {
            $this->attributes['backup_codes'] = null;
            return;
        }

        // Keep the historical used-code ledger when an administrator replaces
        // the remaining codes from the edit form.
        $used = self::decodeUsedRecords($this->getRawOriginal('backup_codes'));
        $this->setBackupCodeState($codes, $used);
    }

    public function getBackupCodesAttribute($value)
    {
        if (!$value) return [];
        $decoded = json_decode($value, true);
        $available = is_array($decoded) && array_key_exists('available', $decoded)
            ? ($decoded['available'] ?? []) : ($decoded ?: []);
        return self::decodeCodes($available);
    }

    /**
     * Decrypted audit ledger of backup codes already handed to automation.
     * The ledger is stored inside the existing encrypted JSON column so no
     * plaintext code is exposed in the database.
     */
    public function getBackupCodesUsedAttribute(): array
    {
        return self::decodeUsedRecords($this->attributes['backup_codes'] ?? null);
    }

    public function setBackupCodeState(array $available, array $used): void
    {
        $available = array_values(array_unique(array_filter(array_map(
            fn($code) => preg_replace('/[\s-]+/', '', (string) $code), $available
        ))));
        $used = array_values(array_filter(array_map(function ($record) {
            if (is_string($record)) $record = ['code' => $record];
            if (!is_array($record) || !isset($record['code'])) return null;
            return [
                'code' => (string) $record['code'],
                'used_at' => isset($record['used_at']) ? (string) $record['used_at'] : now()->toIso8601String(),
                'user_id' => isset($record['user_id']) ? (int) $record['user_id'] : null,
                'attempt_id' => isset($record['attempt_id']) ? (string) $record['attempt_id'] : null,
            ];
        }, $used)));

        $this->attributes['backup_codes'] = json_encode([
            'available' => array_map(fn($code) => self::encryptCode($code), $available),
            'used' => array_map(fn($record) => [
                'code' => self::encryptCode($record['code']),
                'used_at' => $record['used_at'],
                'user_id' => $record['user_id'],
                'attempt_id' => $record['attempt_id'],
            ], $used),
        ]);
    }

    public function getPasswordAttribute()
    {
        return $this->password_encrypted ? Crypt::decryptString($this->password_encrypted) : null;
    }

    public function getBackupCodesRemainingCountAttribute()
    {
        return is_array($this->backup_codes) ? count($this->backup_codes) : 0;
    }

    public function getBackupCodesUsedCountAttribute()
    {
        return count($this->backup_codes_used);
    }

    private static function encryptCode(string $code): string
    {
        return str_starts_with($code, 'enc:') ? $code : 'enc:'.Crypt::encryptString($code);
    }

    private static function decryptCode($code): ?string
    {
        if (!is_string($code) || $code === '') return null;
        try {
            return str_starts_with($code, 'enc:')
                ? Crypt::decryptString(substr($code, 4)) : $code;
        } catch (\Throwable $e) {
            return null;
        }
    }

    private static function decodeCodes($codes): array
    {
        if (!is_array($codes)) return [];
        return array_values(array_filter(array_map(fn($code) => self::decryptCode($code), $codes)));
    }

    private static function decodeUsedRecords($raw): array
    {
        if (!$raw) return [];
        $decoded = is_string($raw) ? json_decode($raw, true) : $raw;
        if (!is_array($decoded) || !array_key_exists('used', $decoded)) return [];
        return array_values(array_filter(array_map(function ($record) {
            if (is_string($record)) $record = ['code' => $record];
            if (!is_array($record)) return null;
            $code = self::decryptCode($record['code'] ?? null);
            if (!$code) return null;
            return [
                'code' => $code,
                'used_at' => $record['used_at'] ?? null,
                'user_id' => isset($record['user_id']) ? (int) $record['user_id'] : null,
                'attempt_id' => $record['attempt_id'] ?? null,
            ];
        }, $decoded['used'])));
    }

    public function getCurrentTotpCodeAttribute(): ?string
    {
        if (!$this->totp_secret_encrypted) return null;
        try {
            $secret = Crypt::decryptString($this->totp_secret_encrypted);
            return (new \PragmaRX\Google2FA\Google2FA())->getCurrentOtp($secret);
        } catch (\Throwable $e) {
            return null;
        }
    }

    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }

    public function scopeAvailable($query)
    {
        return $query->where('status', 'active')->whereNull('assigned_to_user_id');
    }

    public function user()
    {
        return $this->belongsTo(User::class, 'assigned_to_user_id');
    }

    public function users()
    {
        return $this->hasMany(User::class, 'google_flow_account_id');
    }

    public function pairings()
    {
        return $this->hasMany(ExtensionPairing::class);
    }

    public function loginAttempts()
    {
        return $this->hasMany(FlowLoginAttempt::class);
    }
}
