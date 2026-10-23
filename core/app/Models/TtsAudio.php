<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TtsAudio extends Model
{
    protected $table = 'tts_audios';

    protected $fillable = [
        'user_id',
        'voice_id',
        'text',
        'path',
        'mime_type',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
