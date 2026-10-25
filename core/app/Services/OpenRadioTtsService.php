<?php

namespace App\Services;

use App\Models\TtsSetting;
use Illuminate\Http\Client\Response;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Cache;
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
        return $this->client()->get($this->url('voices'), $this->voiceQuery($search, $page, $pageSize, $filters, $provider));
    }

    /**
     * Combine the provider catalogs into the same all-voices feed used by OpenRadio's library.
     * The upstream `provider=all` response currently falls back to ElevenLabs only.
     */
    public function allVoices(?string $search = null, int $page = 1, int $pageSize = 24, array $filters = []): array
    {
        $page = max(1, $page);
        $pageSize = max(1, min(100, $pageSize));
        $providers = ['elevenlabs', 'minimax', 'edge', 'fishaudio'];
        $cacheKey = 'openradio.voice-counts.' . sha1(json_encode([
            'search' => $search,
            'filters' => $filters,
        ]));
        $counts = Cache::remember($cacheKey, now()->addMinutes(5), function () use ($providers, $search, $filters) {
            $result = [];
            foreach ($providers as $provider) {
                $response = $this->client()->get($this->url('voices'), $this->voiceQuery($search, 1, 1, $filters, $provider));
                $result[$provider] = $response->successful() ? (int) ($response->json('total') ?? 0) : 0;
            }

            return $result;
        });

        $total = array_sum($counts);
        $offset = ($page - 1) * $pageSize;
        $items = [];

        foreach ($providers as $provider) {
            $providerTotal = (int) ($counts[$provider] ?? 0);
            if ($offset >= $providerTotal) {
                $offset -= $providerTotal;
                continue;
            }

            $providerOffset = $offset;
            $offset = 0;
            while ($providerOffset < $providerTotal && count($items) < $pageSize) {
                $providerPage = intdiv($providerOffset, $pageSize) + 1;
                $skip = $providerOffset % $pageSize;
                $response = $this->client()->get($this->url('voices'), $this->voiceQuery($search, $providerPage, $pageSize, $filters, $provider));
                $rows = $response->successful() ? $response->json('data', []) : [];
                $rows = is_array($rows) ? array_slice($rows, $skip) : [];
                foreach ($rows as &$row) {
                    if (is_array($row)) {
                        $row['provider'] = $row['provider'] ?? $provider;
                    }
                }
                unset($row);
                $items = array_merge($items, $rows);
                $nextOffset = $providerPage * $pageSize;
                $providerOffset = $nextOffset > $providerOffset ? $nextOffset : $providerOffset + $pageSize;
                if (!$rows) {
                    break;
                }
            }

            if (count($items) >= $pageSize) {
                break;
            }
        }

        return [
            'success' => true,
            'data' => array_slice($items, 0, $pageSize),
            'page' => $page,
            'page_size' => $pageSize,
            'total' => $total,
            'provider_totals' => $counts,
        ];
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

    /**
     * The only account endpoint exposed by OpenRadio's developer API. The
     * caller must whitelist fields before returning anything to a browser.
     */
    public function account(): Response
    {
        return $this->client()->get($this->url('me'));
    }

    /**
     * Return the authenticated account's reusable voice clones. Clone
     * management stays server-side so the OpenRadio token is never exposed
     * to a browser.
     */
    public function voiceClones(): Response
    {
        return $this->client()->get($this->url('voice-clones'));
    }

    /**
     * Create a clone from a short consented sample. The endpoint accepts the
     * same multipart fields used by OpenRadio's Voice Cloning tool.
     */
    public function createVoiceClone(UploadedFile $audio, string $name): Response
    {
        $stream = fopen($audio->getRealPath(), 'rb');

        try {
            return $this->client()
                ->attach('audio', $stream, $audio->getClientOriginalName())
                ->post($this->url('voice-clone'), [
                    'name' => $name,
                    'consent' => 'true',
                ]);
        } finally {
            if (is_resource($stream)) {
                fclose($stream);
            }
        }
    }

    public function deleteVoiceClone(string $clone): Response
    {
        return $this->client()->delete($this->url('voice-clones/' . rawurlencode($clone)));
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

    private function voiceQuery(?string $search, int $page, int $pageSize, array $filters, ?string $provider): array
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

        return $query;
    }

    private function url(string $path): string
    {
        return self::BASE_URL . '/' . ltrim($path, '/');
    }
}
