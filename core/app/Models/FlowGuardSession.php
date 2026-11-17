<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class FlowGuardSession extends Model
{
    protected $fillable = [
        'user_id', 'token_hash', 'installation_id', 'user_agent', 'ip_address',
        'expires_at', 'last_seen_at', 'revoked_at',
    ];

    protected $casts = [
        'expires_at' => 'datetime',
        'last_seen_at' => 'datetime',
        'revoked_at' => 'datetime',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function isActive(): bool
    {
        return !$this->revoked_at && $this->expires_at && $this->expires_at->isFuture();
    }
}
