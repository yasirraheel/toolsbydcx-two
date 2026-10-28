<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MusicGeneration extends Model
{
    protected $fillable = [
        'user_id',
        'mode',
        'description',
        'title',
        'lyrics',
        'tags',
        'vocal_gender',
        'make_instrumental',
        'async',
        'task_id',
        'status',
        'audio_path',
        'mime_type',
        'provider_data',
        'error_message',
    ];

    protected $casts = [
        'make_instrumental' => 'boolean',
        'async' => 'boolean',
        'provider_data' => 'array',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
