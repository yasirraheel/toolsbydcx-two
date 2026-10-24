<?php

namespace App\Services;

use App\Models\TtsSetting;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;

class OpenRadioTtsService
{
    private const BASE_URL = 'https://openradio.io/api/v1';

    public function setting(): ?TtsSetting
    {
        return TtsSetting::query()->first();
    }

    public function configured(): bool
    {
        $setting = $this->setting();

        return $setting
            && $setting->enabled
            && $setting->hasApiKey();
    }

    public function voices(?string $search = null, int $page = 1, int $pageSize = 24, array $filters = [], ?string $provider = 'elevenlabs'): Response
    {
        $query = [
            'page' => max(1, $page),
            'page_size' => max(1, min(100, $pageSize)),
        ];
        if (filled($provider) && strtolower($provider) !== 'all') {
            $query['provider'] = $provider;
        }
        if (filled($search)) {
            $query['search'] = $search;
        }
        foreach (['language', 'accent', 'gender', 'age', 'category'] as $filter) {
            if (filled($filters[$filter] ?? null)) {
                $query[$filter] = $filters[$filter];
            }
        }

        return $this->client()->get($this->url('voices'), $query);
    }

    public function synthesize(string $text, string $voiceId): Response
    {
        return $this->client()->post($this->url('tts'), [
            'text' => $text,
            'voice_id' => $voiceId,
        ]);
    }

    public function task(string $taskId): Response
    {
        return $this->client()->get($this->url('tasks/' . rawurlencode($taskId)));
    }

    private function client()
    {
        $setting = $this->setting();
        $apiKey = $setting?->decryptedApiKey();

        return Http::withToken((string) $apiKey)
            ->acceptJson()
            ->asJson()
            // Do not follow redirects with an Authorization header.
            ->withOptions(['allow_redirects' => false])
            ->timeout(45)
            ->connectTimeout(10);
    }

    private function url(string $path): string
    {
        return self::BASE_URL . '/' . ltrim($path, '/');
    }
}
