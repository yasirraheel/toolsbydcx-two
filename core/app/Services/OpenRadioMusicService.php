<?php

namespace App\Services;

use App\Models\TtsSetting;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;

class OpenRadioMusicService
{
    private const BASE_URL = 'https://openradio.io/api/v1';

    public function configured(): bool
    {
        $setting = TtsSetting::query()->first();

        return $setting
            && $setting->enabled
            && $setting->hasApiKey();
    }

    public function generate(array $payload): Response
    {
        return $this->client()->post($this->url('music'), $payload);
    }

    public function task(string $taskId): Response
    {
        return $this->client()->get($this->url('tasks/' . rawurlencode($taskId)));
    }

    public function account(): Response
    {
        return $this->client()->get($this->url('me'));
    }

    private function client()
    {
        $setting = TtsSetting::query()->first();

        return Http::withToken((string) ($setting?->decryptedApiKey() ?? ''))
            ->acceptJson()
            ->asJson()
            ->withOptions(['allow_redirects' => false])
            ->timeout(60)
            ->connectTimeout(10);
    }

    private function url(string $path): string
    {
        return self::BASE_URL . '/' . ltrim($path, '/');
    }
}
