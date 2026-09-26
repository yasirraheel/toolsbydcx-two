<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ExtensionPairing extends Model
{
    protected $fillable = [
        'user_id',
        'google_flow_account_id',
        'pairing_code',
        'access_token',
        'installation_id',
        'uninstall_token',
        'expires_at',
        'browser',
        'extension_version',
        'is_active',
    ];

    protected $casts = [
        'expires_at' => 'datetime',
        'is_active' => 'boolean',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function googleFlowAccount()
    {
        return $this->belongsTo(GoogleFlowAccount::class);
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function isExpired()
    {
        return $this->expires_at && $this->expires_at->isPast();
    }
}
