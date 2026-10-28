<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use App\Models\MusicGeneration;
use App\Services\OpenRadioMusicService;
use Illuminate\Http\Client\Response;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class MusicController extends Controller
{
    public function __construct(private readonly OpenRadioMusicService $music)
    {
    }

    public function index(Request $request)
    {
        $this->ensureAccess($request);

        return view('templates.basic.user.music', [
            'pageTitle' => 'AI Music',
            'configured' => $this->music->configured(),
        ]);
    }

    public function studioData(Request $request)
    {
        $this->ensureAccess($request);

        $account = ['available' => false, 'credits' => null];
        if ($this->music->configured()) {
            try {
                $upstream = $this->music->account();
                if ($upstream->successful() && is_array($upstream->json())) {
                    $source = is_array($upstream->json('data')) ? $upstream->json('data') : $upstream->json();
                    $credits = $source['credits'] ?? $source['balance'] ?? null;
                    $account = [
                        'available' => is_numeric($credits),
                        'credits' => is_numeric($credits) ? (float) $credits : null,
                    ];
                }
            } catch (\Throwable) {
                // Keep the composer usable while the account endpoint is unavailable.
            }
        }

        return response()->json([
            'success' => true,
            'account' => $account,
            'pricing' => ['simple' => 4000, 'custom' => 4000],
            'max' => ['description' => 500, 'title' => 80, 'lyrics' => 5000, 'tags' => 500],
        ])->header('Cache-Control', 'no-store');
    }

    public function history(Request $request)
    {
        $this->ensureAccess($request);

        $items = MusicGeneration::query()
            ->where('user_id', $request->user()->id)
            ->latest()
            ->limit(50)
            ->get()
            ->map(fn (MusicGeneration $generation) => $this->publicGeneration($generation))
            ->values();

        return response()->json(['success' => true, 'generations' => $items])
            ->header('Cache-Control', 'no-store');
    }

    public function generate(Request $request)
    {
        $this->ensureAccess($request);
        if (!$this->music->configured()) {
            return response()->json(['success' => false, 'message' => 'AI Music is not configured yet.'], 503);
        }

        $validated = $request->validate([
            'mode' => ['required', 'in:simple,custom'],
            'description' => ['nullable', 'string', 'max:500'],
            'make_instrumental' => ['nullable', 'boolean'],
            'title' => ['nullable', 'string', 'max:80'],
            'lyrics' => ['nullable', 'string', 'max:5000'],
            'tags' => ['nullable', 'string', 'max:500'],
            'vocal_gender' => ['nullable', 'in:m,f'],
            'async' => ['nullable', 'boolean'],
        ]);

        $mode = $validated['mode'];
        if ($mode === 'simple' && blank(trim((string) ($validated['description'] ?? '')))) {
            return response()->json(['success' => false, 'message' => 'Add a short description of the song first.'], 422);
        }
        if ($mode === 'custom' && blank(trim((string) ($validated['lyrics'] ?? '')))) {
            return response()->json(['success' => false, 'message' => 'Add lyrics for custom mode first.'], 422);
        }

        $payload = [
            'mode' => $mode,
            'make_instrumental' => $request->boolean('make_instrumental'),
            'async' => true,
        ];
        foreach (['description', 'title', 'lyrics', 'tags', 'vocal_gender'] as $field) {
            if (filled($validated[$field] ?? null)) {
                $payload[$field] = trim((string) $validated[$field]);
            }
        }

        $upstream = $this->music->generate($payload);
        if (!$upstream->successful()) {
            return $this->forwardError($upstream);
        }

        if ($this->isAudioResponse($upstream)) {
            $generation = MusicGeneration::create($this->generationAttributes($request, $payload));
            $this->saveAudio($generation, $upstream->body(), (string) $upstream->header('Content-Type'));

            return response()->json(['success' => true, 'generation' => $this->publicGeneration($generation)]);
        }

        $responsePayload = $upstream->json();
        $responsePayload = is_array($responsePayload) ? $responsePayload : [];
        $generation = MusicGeneration::create(array_merge(
            $this->generationAttributes($request, $payload),
            [
                'task_id' => $this->findTaskId($responsePayload),
                'status' => $this->normalizeStatus($this->findStatus($responsePayload) ?: 'queued'),
                'provider_data' => $responsePayload,
            ]
        ));

        $audioUrl = $this->findAudioUrl($responsePayload);
        if ($audioUrl) {
            $this->saveRemoteAudio($generation, $audioUrl);
        }

        return response()->json([
            'success' => true,
            'generation' => $this->publicGeneration($generation->fresh()),
        ])->header('Cache-Control', 'no-store');
    }

    public function task(Request $request, MusicGeneration $generation)
    {
        $this->ensureAccess($request);
        abort_unless($generation->user_id === $request->user()->id, 404);

        if ($generation->status === 'completed' || $generation->status === 'failed') {
            return response()->json(['success' => true, 'generation' => $this->publicGeneration($generation)]);
        }
        if (blank($generation->task_id)) {
            return response()->json(['success' => true, 'generation' => $this->publicGeneration($generation)]);
        }

        $upstream = $this->music->task($generation->task_id);
        if (!$upstream->successful()) {
            return $this->forwardError($upstream);
        }

        $payload = $upstream->json();
        $payload = is_array($payload) ? $payload : [];
        $status = $this->normalizeStatus($this->findStatus($payload));
        $generation->provider_data = $payload;

        if (in_array($status, ['failed', 'error', 'cancelled', 'canceled'], true)) {
            $generation->status = 'failed';
            $generation->error_message = 'The music generation could not be completed.';
        } elseif ($audioUrl = $this->findAudioUrl($payload)) {
            if (!$this->saveRemoteAudio($generation, $audioUrl)) {
                $generation->status = 'failed';
                $generation->error_message = 'The generated music could not be saved.';
            }
        } elseif (filled($status)) {
            $generation->status = $status;
        }

        $generation->save();

        return response()->json(['success' => true, 'generation' => $this->publicGeneration($generation->fresh())])
            ->header('Cache-Control', 'no-store');
    }

    public function audio(Request $request, MusicGeneration $generation)
    {
        $this->ensureAccess($request);
        abort_unless($generation->user_id === $request->user()->id && $generation->audio_path, 404);
        abort_unless(Storage::disk('local')->exists($generation->audio_path), 404);

        return response()->file(Storage::disk('local')->path($generation->audio_path), [
            'Content-Type' => $generation->mime_type ?: 'audio/mpeg',
            'Cache-Control' => 'private, max-age=3600',
            'Accept-Ranges' => 'bytes',
        ]);
    }

    public function delete(Request $request, MusicGeneration $generation)
    {
        $this->ensureAccess($request);
        abort_unless($generation->user_id === $request->user()->id, 404);
        if ($generation->audio_path) {
            Storage::disk('local')->delete($generation->audio_path);
        }
        $generation->delete();

        return response()->json(['success' => true]);
    }

    private function ensureAccess(Request $request): void
    {
        abort_unless($request->user()?->tts_access === true, 403, 'AI Music access has not been assigned to this account.');
    }

    private function generationAttributes(Request $request, array $payload): array
    {
        return [
            'user_id' => $request->user()->id,
            'mode' => $payload['mode'],
            'description' => $payload['description'] ?? null,
            'title' => $payload['title'] ?? null,
            'lyrics' => $payload['lyrics'] ?? null,
            'tags' => $payload['tags'] ?? null,
            'vocal_gender' => $payload['vocal_gender'] ?? null,
            'make_instrumental' => (bool) ($payload['make_instrumental'] ?? false),
            'async' => true,
            'status' => 'queued',
        ];
    }

    private function publicGeneration(MusicGeneration $generation): array
    {
        return [
            'id' => $generation->id,
            'mode' => $generation->mode,
            'title' => $generation->title,
            'description' => $generation->description,
            'status' => $generation->status,
            'error_message' => $generation->error_message,
            'audio_url' => $generation->audio_path ? route('user.music.audio', ['generation' => $generation]) : null,
            'created_at' => $generation->created_at?->toIso8601String(),
        ];
    }

    private function findTaskId(array $payload): ?string
    {
        foreach (['task_id', 'taskId', 'id'] as $key) {
            if (filled($payload[$key] ?? null)) return (string) $payload[$key];
        }
        foreach (['task_ids', 'taskIds'] as $key) {
            if (is_array($payload[$key] ?? null) && filled($payload[$key][0] ?? null)) {
                return (string) $payload[$key][0];
            }
        }
        foreach (['task', 'data', 'result'] as $key) {
            if (is_array($payload[$key] ?? null)) {
                $id = $this->findTaskId($payload[$key]);
                if ($id) return $id;
            }
        }
        return null;
    }

    private function findAudioUrl(array $payload): ?string
    {
        foreach (['audio_url', 'audioUrl', 'download_url', 'downloadUrl', 'mp3_url', 'mp3Url', 'url'] as $key) {
            if (filter_var($payload[$key] ?? null, FILTER_VALIDATE_URL)) return (string) $payload[$key];
        }
        foreach (['audio', 'tracks', 'data', 'result', 'output', 'fileInfo', 'file_info'] as $key) {
            if (is_array($payload[$key] ?? null)) {
                $url = $this->findAudioUrl($payload[$key]);
                if ($url) return $url;
            }
        }
        foreach ($payload as $value) {
            if (is_array($value)) {
                $url = $this->findAudioUrl($value);
                if ($url) return $url;
            }
        }
        return null;
    }

    private function normalizeStatus(?string $status): ?string
    {
        $status = strtolower(trim((string) $status));
        return match ($status) {
            'succeeded', 'success', 'done', 'complete', 'completed', 'ready' => 'completed',
            'failed', 'error', 'cancelled', 'canceled', 'expired' => 'failed',
            'processing', 'running', 'in_progress', 'in-progress' => 'processing',
            default => $status !== '' ? $status : null,
        };
    }

    private function findStatus(array $payload): ?string
    {
        foreach (['status', 'state'] as $key) {
            if (filled($payload[$key] ?? null)) return strtolower((string) $payload[$key]);
        }
        foreach (['task', 'data', 'result'] as $key) {
            if (is_array($payload[$key] ?? null)) {
                $status = $this->findStatus($payload[$key]);
                if ($status) return $status;
            }
        }
        return null;
    }

    private function isAudioResponse(Response $response): bool
    {
        return str_starts_with(strtolower((string) $response->header('Content-Type')), 'audio/');
    }

    private function saveRemoteAudio(MusicGeneration $generation, string $audioUrl): bool
    {
        try {
            $source = Http::accept('*/*')->timeout(90)->connectTimeout(10)->get($audioUrl);
            if (!$source->successful() || !str_starts_with(strtolower((string) $source->header('Content-Type')), 'audio/')) {
                return false;
            }
            $this->saveAudio($generation, $source->body(), (string) $source->header('Content-Type'));
            return true;
        } catch (\Throwable) {
            return false;
        }
    }

    private function saveAudio(MusicGeneration $generation, string $contents, string $mimeType): void
    {
        $mime = trim(explode(';', $mimeType)[0]) ?: 'audio/mpeg';
        $extension = match (strtolower($mime)) {
            'audio/wav', 'audio/x-wav' => 'wav',
            'audio/ogg' => 'ogg',
            'audio/mp4', 'audio/x-m4a' => 'm4a',
            default => 'mp3',
        };
        $path = 'music/' . $generation->user_id . '/' . Str::uuid() . '.' . $extension;
        Storage::disk('local')->put($path, $contents);
        $generation->update(['audio_path' => $path, 'mime_type' => $mime, 'status' => 'completed', 'error_message' => null]);
    }

    private function forwardError(Response $upstream)
    {
        return response()->json([
            'success' => false,
            'message' => 'The music request could not be completed right now. Please try again.',
        ], $upstream->status() >= 400 ? $upstream->status() : 502)
            ->header('Cache-Control', 'no-store');
    }
}
