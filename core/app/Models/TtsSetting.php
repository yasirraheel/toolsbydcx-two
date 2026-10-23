<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Crypt;
use Throwable;

class TtsSetting extends Model
{
    protected $fillable = [
        'provider',
        'api_key_encrypted',
        'default_voice_id',
        'enabled',
    ];

    protected $casts = [
        'enabled' => 'boolean',
    ];

    protected $hidden = [
        'api_key_encrypted',
    ];

    public function decryptedApiKey(): ?string
    {
        if (!$this->api_key_encrypted) {
            return null;
        }

        try {
            return Crypt::decryptString($this->api_key_encrypted);
        } catch (Throwable) {
            return null;
        }
    }

    public function hasApiKey(): bool
    {
        return filled($this->api_key_encrypted) && filled($this->decryptedApiKey());
    }
}
