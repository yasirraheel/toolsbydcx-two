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

        return view('templates.basic.user.tts-studio', [
            'pageTitle' => $pageTitle,
            'configured' => $this->tts->configured(),
            'defaultVoiceId' => $setting?->default_voice_id,
        ]);
    }

    /**
     * Keep the complete API-backed voice library available as its own page.
     * The Studio editor lives at /user/tts; this page preserves the original
     * searchable, filterable, paginated library experience.
     */
    public function library(Request $request)
    {
        $this->ensureAccess($request);
        $setting = $this->tts->setting();

        return view('templates.basic.user.tts', [
            'pageTitle' => 'Voice Library',
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

        $search = $request->input('search');
        $page = (int) $request->input('page', 1);
        $pageSize = (int) $request->input('page_size', 24);
        $filters = $request->only(['language', 'accent', 'gender', 'age', 'category']);
        $provider = $request->input('provider', 'elevenlabs');

        if (strtolower((string) $provider) === 'all') {
            return response()->json($this->forwardVoicePayload($this->tts->allVoices($search, $page, $pageSize, $filters)))
                ->header('Cache-Control', 'no-store');
        }

        return $this->forwardVoices($this->tts->voices($search, $page, $pageSize, $filters, $provider));
    }

    /**
     * Browser-safe Studio bootstrap data. The API secret and raw /me payload
     * never leave Laravel; only display-safe account fields are returned.
     */
    public function studioData(Request $request)
    {
        $this->ensureAccess($request);
        if (!$this->tts->configured()) {
            return response()->json(['success' => false, 'message' => 'Text-to-speech is not configured yet.'], 503);
        }

        $account = [
            'available' => false,
            'credits' => null,
            'plan' => null,
            'plan_expires_at' => null,
        ];

        try {
            $upstream = $this->tts->account();
            if ($upstream->successful() && is_array($upstream->json())) {
                $payload = $upstream->json();
                $source = is_array($payload['data'] ?? null) ? $payload['data'] : $payload;
                $credits = $source['credits'] ?? null;

                $account = [
                    'available' => is_numeric($credits),
                    'credits' => is_numeric($credits) ? (float) $credits : null,
                    'plan' => filled($source['plan'] ?? null) ? (string) $source['plan'] : null,
                    'plan_expires_at' => filled($source['plan_expires_at'] ?? null) ? (string) $source['plan_expires_at'] : null,
                ];
            }
        } catch (\Throwable) {
            // The Studio remains usable if the account endpoint is briefly unavailable.
        }

        return response()->json([
            'success' => true,
            'account' => $account,
            // OpenRadio's official Studio publishes these as editor helper
            // menus. They are served from our Studio endpoint so the page has
            // one live source of truth and no duplicated browser constants.
            'editor_helpers' => $this->studioEditorHelpers(),
            'providers' => [
                ['id' => 'elevenlabs', 'label' => 'ElevenLabs'],
                ['id' => 'minimax', 'label' => 'Fire TTS'],
                ['id' => 'edge', 'label' => 'Edge TTS'],
                ['id' => 'fishaudio', 'label' => 'Fish Audio'],
            ],
            'models' => [
                'elevenlabs' => [
                    ['id' => 'api-default', 'label' => 'OpenRadio API default for the selected voice'],
                ],
            ],
            'languages' => ['Detect Language', 'English', 'Urdu', 'Arabic', 'Hindi', 'Spanish', 'French', 'German'],
            'pricing' => [
                'elevenlabs' => ['multiplier' => 1.2, 'free' => false],
                'minimax' => ['multiplier' => 1, 'free' => false],
                'fishaudio' => ['multiplier' => 1, 'free' => false],
                'edge' => ['multiplier' => 0, 'free' => true],
            ],
            'public_api_capabilities' => [
                'account' => true,
                'voices' => true,
                'history' => false,
                'models' => false,
            ],
        ])->header('Cache-Control', 'no-store');
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
            'text' => ['required', 'string', 'max:60000'],
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
            'text' => ['required', 'string', 'max:60000'],
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

        $payload = $this->forwardVoicePayload($upstream->json());
        if (!is_array($payload) || !is_array($payload['data'] ?? null)) {
            return $this->forward($upstream);
        }

        return response()->json($payload, $upstream->status())
            ->header('Cache-Control', 'no-store');
    }

    private function forwardVoicePayload(?array $payload): array
    {
        if (!is_array($payload)) {
            return ['success' => false, 'message' => 'OpenRadio returned an invalid response.'];
        }

        foreach ($payload['data'] ?? [] as &$voice) {
            if (is_array($voice) && filled($voice['language'] ?? null)) {
                $voice['language_label'] = $this->languageLabel((string) $voice['language']);
            }
        }
        unset($voice);

        return $payload;
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

    private function studioEditorHelpers(): array
    {
        return [
            'emotions' => [
                ['id' => 'happy', 'label' => 'Happy', 'insert' => '[happy]'],
                ['id' => 'excited', 'label' => 'Excited', 'insert' => '[excited]'],
                ['id' => 'sad', 'label' => 'Sad', 'insert' => '[sad]'],
                ['id' => 'angry', 'label' => 'Angry', 'insert' => '[angry]'],
                ['id' => 'fearful', 'label' => 'Fearful', 'insert' => '[fearful]'],
                ['id' => 'surprised', 'label' => 'Surprised', 'insert' => '[surprised]'],
                ['id' => 'sarcastic', 'label' => 'Sarcastic', 'insert' => '[sarcastic]'],
                ['id' => 'curious', 'label' => 'Curious', 'insert' => '[curious]'],
                ['id' => 'serious', 'label' => 'Serious', 'insert' => '[serious]'],
                ['id' => 'shouting', 'label' => 'Shouting', 'insert' => '[shouting]'],
                ['id' => 'disgusted', 'label' => 'Disgusted', 'insert' => '[disgusted]'],
            ],
            'pauses' => [
                ['id' => 'short', 'label' => 'Short', 'insert' => '<break time="500ms"/>'],
                ['id' => 'medium', 'label' => 'Medium', 'insert' => '<break time="1000ms"/>'],
                ['id' => 'long', 'label' => 'Long', 'insert' => '<break time="2000ms"/>'],
                ['id' => 'x-long', 'label' => 'X-Long', 'insert' => '<break time="3000ms"/>'],
            ],
            'sound_tags' => [
                ['id' => 'laughs', 'label' => 'Laugh [laughs]', 'insert' => '[laughs]'],
                ['id' => 'chuckles', 'label' => 'Chuckle [chuckles]', 'insert' => '[chuckles]'],
                ['id' => 'giggles', 'label' => 'Giggle [giggles]', 'insert' => '[giggles]'],
                ['id' => 'sighs', 'label' => 'Sigh [sighs]', 'insert' => '[sighs]'],
                ['id' => 'gasps', 'label' => 'Gasp [gasps]', 'insert' => '[gasps]'],
                ['id' => 'whispers', 'label' => 'Whisper [whispers]', 'insert' => '[whispers]'],
                ['id' => 'crying', 'label' => 'Crying [crying]', 'insert' => '[crying]'],
                ['id' => 'coughs', 'label' => 'Cough [coughs]', 'insert' => '[coughs]'],
                ['id' => 'clears-throat', 'label' => 'Clear throat', 'insert' => '[clears throat]'],
                ['id' => 'applause', 'label' => 'Applause [applause]', 'insert' => '[applause]'],
                ['id' => 'breathing', 'label' => 'Breathing [breathing]', 'insert' => '[breathing]'],
            ],
        ];
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
