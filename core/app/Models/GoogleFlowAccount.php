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

    public function getPasswordAttribute()
    {
        return $this->password_encrypted ? Crypt::decryptString($this->password_encrypted) : null;
    }

    public function getBackupCodesRemainingCountAttribute()
    {
        return is_array($this->backup_codes) ? count($this->backup_codes) : 0;
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
