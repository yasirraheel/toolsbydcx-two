<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use App\Services\OpenRadioTtsService;
use Illuminate\Http\Client\Response;
use Illuminate\Http\Request;

class TextToSpeechController extends Controller
{
    public function __construct(private readonly OpenRadioTtsService $tts)
    {
    }

    public function index()
    {
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
        if (!$this->tts->configured()) {
            return response()->json(['success' => false, 'message' => 'Text-to-speech is not configured yet.'], 503);
        }

        $request->validate([
            'search' => ['nullable', 'string', 'max:80'],
        ]);

        return $this->forward($this->tts->voices($request->input('search')));
    }

    public function generate(Request $request)
    {
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

        return $this->forward($this->tts->synthesize(trim($validated['text']), $voiceId));
    }

    public function task(string $task)
    {
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
}
