<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class FlowGuardCreditReservation extends Model
{
    public $incrementing = false;
    protected $keyType = 'string';

    protected $fillable = [
        'id', 'user_id', 'session_id', 'amount', 'status', 'idempotency_key',
        'metadata', 'expires_at', 'settled_at',
    ];

    protected $casts = [
        'amount' => 'integer',
        'metadata' => 'array',
        'expires_at' => 'datetime',
        'settled_at' => 'datetime',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function session()
    {
        return $this->belongsTo(FlowGuardSession::class, 'session_id');
    }
}
