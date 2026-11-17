<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class FlowGuardProject extends Model
{
    protected $fillable = [
        'user_id', 'external_project_id', 'name', 'url', 'last_seen_at',
    ];

    protected $casts = [
        'last_seen_at' => 'datetime',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
