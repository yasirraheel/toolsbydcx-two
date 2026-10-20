<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class UserChatThread extends Model
{
    protected $fillable = [
        'user_id',
        'provider',
        'chat_id',
        'title',
        'last_seen_at',
    ];

    protected $casts = [
        'last_seen_at' => 'datetime',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
