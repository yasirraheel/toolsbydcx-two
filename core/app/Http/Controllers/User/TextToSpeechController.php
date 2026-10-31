<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use App\Models\TtsAudio;
use App\Models\TtsVoiceClone;
use App\Services\OpenRadioTtsService;
use App\Services\UserAudioCreditService;
use Illuminate\Http\Client\Response;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class TextToSpeechController extends Controller
{
    public function __construct(
        private readonly OpenRadioTtsService $tts,
        private readonly UserAudioCreditService $credits
    )
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

    public function cloning(Request $request)
    {
        $this->ensureAccess($request);

        return view('templates.basic.user.voice-cloning', [
            'pageTitle' => 'Voice Cloning',
            'configured' => $this->tts->configured(),
        ]);
    }

    public function clones(Request $request)
    {
        $this->ensureAccess($request);
        if (!$this->tts->configured()) {
            return response()->json(['success' => false, 'message' => 'Text-to-speech is not configured yet.'], 503);
        }

        $upstream = $this->tts->voiceClones();
        if (!$upstream->successful()) {
            return $this->forwardClone($upstream);
        }

        $payload = $upstream->json();
        $payload = is_array($payload) ? $payload : [];
        $remote = $this->cloneRows($payload);
        $local = TtsVoiceClone::query()
            ->where('user_id', $request->user()->id)
            ->latest()
            ->get()
            ->map(fn (TtsVoiceClone $clone) => [
                'id' => $clone->clone_id,
                'voice_id' => $clone->clone_id,
                'name' => $clone->name,
                'description' => $clone->description,
                'provider' => 'clone',
            ])
            ->all();

        $merged = collect(array_merge($local, $remote))
            ->filter(fn ($clone) => filled($clone['voice_id'] ?? null))
            ->unique(fn ($clone) => (string) ($clone['voice_id'] ?? $clone['id'] ?? ''))
            ->values()
            ->all();

        if (array_key_exists('data', $payload)) {
            $payload['data'] = $merged;
        } else {
            $payload['clones'] = $merged;
        }
        $payload['total'] = count($merged);

        return response()->json($payload, $upstream->status())
            ->header('Cache-Control', 'no-store');
    }

    public function createClone(Request $request)
    {
        $this->ensureAccess($request);
        if (!$this->tts->configured()) {
            return response()->json(['success' => false, 'message' => 'Text-to-speech is not configured yet.'], 503);
        }

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:80'],
            // OpenRadio's voice-clones endpoint accepts MP3, WAV, M4A and
            // FLAC reference samples up to 20 MB.
            'audio' => ['required', 'file', 'mimes:mp3,wav,m4a,aac,flac', 'max:20480'],
            'consent' => ['accepted'],
        ]);

        $upstream = $this->tts->createVoiceClone(
            $validated['audio'],
            trim($validated['name'])
        );

        if ($upstream->successful()) {
            $payload = $upstream->json();
            $rows = is_array($payload) ? $this->cloneRows($payload) : [];
            $clone = $rows[0] ?? null;
            if ($clone && filled($clone['voice_id'] ?? null)) {
                TtsVoiceClone::updateOrCreate(
                    ['user_id' => $request->user()->id, 'clone_id' => (string) $clone['voice_id']],
                    [
                        'name' => (string) ($clone['name'] ?? $validated['name']),
                        'description' => $clone['description'] ?? null,
                        'metadata' => is_array($payload) ? $payload : null,
                    ]
                );
            }
        }

        return $this->forwardClone($upstream);
    }

    public function deleteClone(Request $request, string $clone)
    {
        $this->ensureAccess($request);
        if (!$this->tts->configured()) {
            return response()->json(['success' => false, 'message' => 'Text-to-speech is not configured yet.'], 503);
        }

        abort_unless(preg_match('/^[A-Za-z0-9_-]{1,180}$/', $clone), 404);

        $upstream = $this->tts->deleteVoiceClone($clone);
        if ($upstream->successful()) {
            TtsVoiceClone::query()
                ->where('user_id', $request->user()->id)
                ->where('clone_id', $clone)
                ->delete();
        }

        return $this->forwardClone($upstream);
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

        return response()->json([
            'success' => true,
            'user_credits' => [
                'limit' => $request->user()->tts_credit_limit,
                'used' => (int) ($request->user()->tts_credits_used ?? 0),
                'remaining' => $this->credits->remaining($request->user()),
            ],
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
                    ['id' => 'eleven_v4', 'label' => 'ElevenLabs V4 — fastest & most emotive (New)'],
                    ['id' => 'eleven_v4_turbo', 'label' => 'ElevenLabs V4 Turbo — ultra-fast emotive'],
                    ['id' => 'eleven_v3', 'label' => 'ElevenLabs V3 — expressive'],
                    ['id' => 'eleven_turbo_v2_5', 'label' => 'ElevenLabs V2.5 — classic fast'],
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
            'model' => ['nullable', 'string', 'in:eleven_v4,eleven_v4_turbo,eleven_v3,eleven_turbo_v2_5'],
        ]);

        $voiceId = trim((string) ($validated['voice_id'] ?? ''));
        if ($voiceId === '') {
            $voiceId = trim((string) ($this->tts->setting()?->default_voice_id ?? ''));
        }
        if ($voiceId === '') {
            return response()->json(['success' => false, 'message' => 'Choose a voice before generating audio.'], 422);
        }

        $text = trim($validated['text']);
        $creditCost = $this->ttsCreditCost($text);
        if (!$this->credits->reserve($request->user(), $creditCost)) {
            return response()->json([
                'success' => false,
                'message' => 'This account does not have enough assigned TTS credits for that request.',
                'remaining' => $this->credits->remaining($request->user()),
            ], 402);
        }

        try {
            $upstream = $this->tts->synthesize($text, $voiceId, $validated['model'] ?? null);
            if ($upstream->successful() && str_starts_with(strtolower((string) $upstream->header('Content-Type')), 'audio/')) {
                return $this->saveAudio($request->user(), $text, $voiceId, $upstream->body(), (string) $upstream->header('Content-Type'));
            }
            $this->credits->refund($request->user(), $creditCost);
        } catch (\Throwable $e) {
            $this->credits->refund($request->user(), $creditCost);
            report($e);
            return response()->json([
                'success' => false,
                'message' => 'The speech service is temporarily unavailable. Your credits were restored; please try again.',
            ], 502)->header('Cache-Control', 'no-store');
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

        if (!$upstream->successful()) {
            $payload = $upstream->json();
            $message = is_array($payload)
                ? ($payload['message'] ?? $payload['error'] ?? null)
                : null;

            return response()->json([
                'success' => false,
                'message' => filled($message) ? 'The speech request could not be completed.' : 'The speech service is temporarily unavailable.',
            ], $upstream->status())
                ->header('Cache-Control', 'no-store');
        }

        $payload = $upstream->json();
        if (!is_array($payload)) {
            $payload = ['success' => false, 'message' => 'The speech service returned an invalid response.'];
        }

        return response()->json($payload, $upstream->status())
            ->header('Cache-Control', 'no-store');
    }

    private function forwardClone(Response $upstream)
    {
        if (!$upstream->successful()) {
            $payload = $upstream->json();
            $message = is_array($payload)
                ? ($payload['message'] ?? $payload['error'] ?? null)
                : null;

            if (!$message || $upstream->status() === 404 || str_contains($upstream->body(), 'Cannot POST')) {
                $message = 'Voice-clone creation is not available through the current speech service integration. Please contact an administrator.';
            }

            return response()->json([
                'success' => false,
                'message' => $message,
            ], 502)->header('Cache-Control', 'no-store');
        }

        return $this->forward($upstream);
    }

    /**
     * Normalize the different clone response envelopes used by OpenRadio.
     * The developer API has returned `data`, `clones`, and `clone` envelopes
     * over time, so the application keeps one browser-safe shape.
     */
    private function cloneRows(array $payload): array
    {
        $rows = $payload['clones'] ?? $payload['data'] ?? null;
        if (is_array($payload['clone'] ?? null)) {
            $rows = [$payload['clone']];
        }
        if (!is_array($rows)) {
            $rows = $this->cloneId($payload) ? [$payload] : [];
        } elseif (array_is_list($rows) === false && $this->cloneId($rows)) {
            $rows = [$rows];
        }

        return collect($rows)
            // Collection::filter passes both value and key to callbacks;
            // PHP's is_array() accepts only one argument, so use an explicit
            // closure to avoid a production ArgumentCountError here.
            ->filter(fn ($clone) => is_array($clone))
            ->map(function (array $clone) {
                $id = $this->cloneId($clone);
                return [
                    'id' => $id,
                    'voice_id' => $id,
                    'name' => (string) ($clone['name'] ?? $clone['display_name'] ?? $clone['label'] ?? 'Voice clone'),
                    'description' => $clone['description'] ?? null,
                    'provider' => 'clone',
                ];
            })
            ->filter(fn (array $clone) => filled($clone['voice_id']))
            ->values()
            ->all();
    }

    private function cloneId(array $clone): ?string
    {
        foreach (['voice_id', 'voiceId', 'clone_id', 'cloneId', 'id'] as $key) {
            if (filled($clone[$key] ?? null)) {
                return (string) $clone[$key];
            }
        }

        return null;
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
            return ['success' => false, 'message' => 'The speech service returned an invalid response.'];
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

    private function ttsCreditCost(string $text): int
    {
        // OpenRadio bills ElevenLabs-style TTS by characters; reserve a small
        // integer amount before the request so concurrent generations cannot
        // overspend a user's assigned quota.
        return max(1, (int) ceil(mb_strlen($text) * 1.2));
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
            'remaining_credits' => $this->credits->remaining($user),
        ])->header('Cache-Control', 'no-store');
    }
}
