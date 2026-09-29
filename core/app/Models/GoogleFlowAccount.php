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
        $this->attributes['backup_codes'] = $codes === null ? null : json_encode(array_map(
            fn($code) => 'enc:'.Crypt::encryptString((string) $code), array_values($codes)
        ));
    }

    public function getBackupCodesAttribute($value)
    {
        if (!$value) return [];
        return array_map(fn($code) => str_starts_with((string) $code, 'enc:')
            ? Crypt::decryptString(substr($code, 4)) : (string) $code, json_decode($value, true) ?: []);
    }

    public function getPasswordAttribute()
    {
        return $this->password_encrypted ? Crypt::decryptString($this->password_encrypted) : null;
    }

    public function getBackupCodesRemainingCountAttribute()
    {
        return is_array($this->backup_codes) ? count($this->backup_codes) : 0;
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

    public function pairings()
    {
        return $this->hasMany(ExtensionPairing::class);
    }

    public function loginAttempts()
    {
        return $this->hasMany(FlowLoginAttempt::class);
    }
}
