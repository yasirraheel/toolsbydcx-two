<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Crypt;

class UserChatThread extends Model
{
    protected $fillable = [
        'user_id',
        'provider',
        'chat_id',
        'title',
        'last_seen_at',
        'messages_encrypted',
        'message_count',
        'share_token',
        'is_public',
        'archived_at',
    ];

    protected $casts = [
        'last_seen_at' => 'datetime',
        'is_public' => 'boolean',
        'archived_at' => 'datetime',
        'message_count' => 'integer',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function archiveMessages(array $messages): void
    {
        $this->messages_encrypted = Crypt::encryptString(json_encode($messages, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
        $this->message_count = count($messages);
        $this->archived_at = now();
    }

    public function decryptedMessages(): array
    {
        if (!$this->messages_encrypted) {
            return [];
        }

        try {
            $messages = json_decode(Crypt::decryptString($this->messages_encrypted), true);
            return is_array($messages) ? $messages : [];
        } catch (\Throwable $e) {
            return [];
        }
    }
}
