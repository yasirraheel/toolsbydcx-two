<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class FlowGuardAccess extends Model
{
    protected $table = 'flow_guard_access';

    protected $fillable = [
        'user_id', 'enabled', 'full_control', 'credit_limit', 'credits_used', 'generation_cost', 'allowed_model', 'allowed_models', 'expires_at',
    ];

    protected $casts = [
        'enabled' => 'boolean',
        'full_control' => 'boolean',
        'credit_limit' => 'integer',
        'credits_used' => 'integer',
        'generation_cost' => 'integer',
        'allowed_model' => 'string',
        'allowed_models' => 'array',
        'expires_at' => 'datetime',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function remainingCredits(): ?int
    {
        return $this->credit_limit === null
            ? null
            : max(0, (int) $this->credit_limit - (int) $this->credits_used);
    }
}
