<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class FlowLoginAttempt extends Model
{
    protected $primaryKey = 'id';
    public $incrementing = false;
    protected $keyType = 'string';

    protected $fillable = [
        'id',
        'user_id',
        'google_flow_account_id',
        'status',
        'expires_at',
        'backup_code_used',
        'otp_attempt_count',
        'outcome',
    ];

    protected $casts = [
        'expires_at' => 'datetime',
        'backup_code_used' => 'boolean',
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
        return $query->where('status', 'in_progress');
    }
}
