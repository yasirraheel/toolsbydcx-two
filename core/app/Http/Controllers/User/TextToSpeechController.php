<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use App\Models\TtsAudio;
use App\Services\OpenRadioTtsService;
use Illuminate\Http\Client\Response;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class TextToSpeechController extends Controller
{
    public function __construct(private readonly OpenRadioTtsService $tts)
    {
    }

    public function index(Request $request)
    {
        $this->ensureAccess($request);
        $pageTitle = 'Text to Speech';
        $setting = $this->tts->setting();

        return view('templates.basic.user.tts', [
            'pageTitle' => $pageTitle,
            'configured' => $this->tts->configured(),
            'defaultVoiceId' => $setting?->default_voice_id,
        ]);
    }

    public function voices(Request $request)
    {
        $this->ensureAccess($request);
        if (!$this->tts->configured()) {
            return response()->json(['success' => false, 'message' => 'Text-to-speech is not configured yet.'], 503);
        }

        $request->validate([
            'search' => ['nullable', 'string', 'max:80'],
            'page' => ['nullable', 'integer', 'min:1', 'max:2000'],
            'page_size' => ['nullable', 'integer', 'min:1', 'max:100'],
            'provider' => ['nullable', 'string', 'max:40'],
            'language' => ['nullable', 'string', 'max:80'],
            'accent' => ['nullable', 'string', 'max:80'],
            'gender' => ['nullable', 'string', 'max:40'],
            'age' => ['nullable', 'string', 'max:40'],
            'category' => ['nullable', 'string', 'max:80'],
        ]);

        return $this->forwardVoices($this->tts->voices(
            $request->input('search'),
            (int) $request->input('page', 1),
            (int) $request->input('page_size', 24),
            $request->only(['language', 'accent', 'gender', 'age', 'category']),
            $request->input('provider', 'elevenlabs')
        ));
    }

    public function history(Request $request)
    {
        $this->ensureAccess($request);

        $audios = TtsAudio::query()
            ->where('user_id', $request->user()->id)
            ->latest()
            ->limit(50)
            ->get()
            ->map(fn (TtsAudio $audio) => [
                'id' => $audio->id,
                'voice_id' => $audio->voice_id,
                'text' => $audio->text,
                'created_at' => $audio->created_at?->toIso8601String(),
                'audio_url' => route('user.tts.audio', ['audio' => $audio]),
            ]);

        return response()->json(['success' => true, 'audios' => $audios])
            ->header('Cache-Control', 'no-store');
    }

    public function audio(Request $request, TtsAudio $audio)
    {
        $this->ensureAccess($request);
        abort_unless($audio->user_id === $request->user()->id, 404);

        if (!Storage::disk('local')->exists($audio->path)) {
            abort(404);
        }

        return response()->file(Storage::disk('local')->path($audio->path), [
            'Content-Type' => $audio->mime_type,
            'Cache-Control' => 'private, max-age=3600',
            'Accept-Ranges' => 'bytes',
        ]);
    }

    public function persist(Request $request)
    {
        $this->ensureAccess($request);

        $validated = $request->validate([
            'audio_url' => ['required', 'url', 'max:2000'],
            'text' => ['required', 'string', 'max:10000'],
            'voice_id' => ['required', 'string', 'max:160'],
        ]);

        $source = Http::accept('*/*')
            ->timeout(45)
            ->connectTimeout(10)
            ->get($validated['audio_url']);

        if (!$source->successful() || !str_starts_with(strtolower((string) $source->header('Content-Type')), 'audio/')) {
            return response()->json(['success' => false, 'message' => 'The generated audio could not be saved.'], 422);
        }

        return $this->saveAudio($request->user(), $validated['text'], $validated['voice_id'], $source->body(), (string) $source->header('Content-Type'));
    }

    public function generate(Request $request)
    {
        $this->ensureAccess($request);
        if (!$this->tts->configured()) {
            return response()->json(['success' => false, 'message' => 'Text-to-speech is not configured yet.'], 503);
        }

        $validated = $request->validate([
            'text' => ['required', 'string', 'max:10000'],
            'voice_id' => ['nullable', 'string', 'max:160'],
        ]);

        $voiceId = trim((string) ($validated['voice_id'] ?? ''));
        if ($voiceId === '') {
            $voiceId = trim((string) ($this->tts->setting()?->default_voice_id ?? ''));
        }
        if ($voiceId === '') {
            return response()->json(['success' => false, 'message' => 'Choose a voice before generating audio.'], 422);
        }

        $upstream = $this->tts->synthesize(trim($validated['text']), $voiceId);
        if ($upstream->successful() && str_starts_with(strtolower((string) $upstream->header('Content-Type')), 'audio/')) {
            return $this->saveAudio($request->user(), trim($validated['text']), $voiceId, $upstream->body(), (string) $upstream->header('Content-Type'));
        }

        return $this->forward($upstream);
    }

    public function task(Request $request, string $task)
    {
        $this->ensureAccess($request);
        if (!$this->tts->configured()) {
            return response()->json(['success' => false, 'message' => 'Text-to-speech is not configured yet.'], 503);
        }

        abort_unless(preg_match('/^[A-Za-z0-9_-]{1,160}$/', $task), 404);

        return $this->forward($this->tts->task($task));
    }

    private function forward(Response $upstream)
    {
        if (str_starts_with(strtolower((string) $upstream->header('Content-Type')), 'audio/')) {
            return response($upstream->body(), $upstream->status())
                ->header('Content-Type', $upstream->header('Content-Type'))
                ->header('Cache-Control', 'no-store');
        }

        $payload = $upstream->json();
        if (!is_array($payload)) {
            $payload = ['success' => false, 'message' => 'OpenRadio returned an invalid response.'];
        }

        return response()->json($payload, $upstream->status())
            ->header('Cache-Control', 'no-store');
    }

    private function forwardVoices(Response $upstream)
    {
        if (!$upstream->successful()) {
            return $this->forward($upstream);
        }

        $payload = $upstream->json();
        if (!is_array($payload) || !is_array($payload['data'] ?? null)) {
            return $this->forward($upstream);
        }

        foreach ($payload['data'] as &$voice) {
            if (is_array($voice) && filled($voice['language'] ?? null)) {
                $voice['language_label'] = $this->languageLabel((string) $voice['language']);
            }
        }
        unset($voice);

        return response()->json($payload, $upstream->status())
            ->header('Cache-Control', 'no-store');
    }

    private function languageLabel(string $language): string
    {
        $language = str_replace('_', '-', trim($language));
        if (class_exists(\Locale::class)) {
            $label = \Locale::getDisplayLanguage($language, 'en');
            if (filled($label)) {
                return $label;
            }
        }

        return $language;
    }

    private function ensureAccess(Request $request): void
    {
        abort_unless($request->user()?->tts_access === true, 403, 'Text-to-Speech access has not been assigned to this account.');
    }

    private function saveAudio($user, string $text, string $voiceId, string $contents, string $mimeType)
    {
        $extension = match (strtolower(trim(explode(';', $mimeType)[0]))) {
            'audio/wav', 'audio/x-wav' => 'wav',
            'audio/ogg' => 'ogg',
            'audio/mp4', 'audio/x-m4a' => 'm4a',
            default => 'mp3',
        };
        $path = 'tts/' . $user->id . '/' . Str::uuid() . '.' . $extension;
        Storage::disk('local')->put($path, $contents);
        $audio = TtsAudio::create([
            'user_id' => $user->id,
            'voice_id' => $voiceId,
            'text' => $text,
            'path' => $path,
            'mime_type' => trim(explode(';', $mimeType)[0]) ?: 'audio/mpeg',
        ]);

        return response()->json([
            'success' => true,
            'audio_id' => $audio->id,
            'audio_url' => route('user.tts.audio', ['audio' => $audio]),
        ])->header('Cache-Control', 'no-store');
    }
}
