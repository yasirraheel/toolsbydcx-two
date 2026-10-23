<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\TtsSetting;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Crypt;

class TextToSpeechController extends Controller
{
    public function index()
    {
        $pageTitle = 'Text to Speech';
        $setting = TtsSetting::query()->first();
        $maskedApiKey = null;

        if ($setting && ($apiKey = $setting->decryptedApiKey())) {
            $maskedApiKey = str_repeat('•', max(0, min(12, strlen($apiKey) - 4))) . substr($apiKey, -4);
        }

        return view('admin.tts.index', compact('pageTitle', 'setting', 'maskedApiKey'));
    }

    public function update(Request $request)
    {
        $validated = $request->validate([
            'api_key' => ['nullable', 'string', 'max:255'],
            'default_voice_id' => ['nullable', 'string', 'max:160'],
        ]);

        $setting = TtsSetting::query()->firstOrNew(['id' => 1]);
        $setting->provider = 'openradio';
        $setting->default_voice_id = trim((string) ($validated['default_voice_id'] ?? '')) ?: null;
        $setting->enabled = $request->boolean('enabled');

        if ($request->boolean('clear_api_key')) {
            $setting->api_key_encrypted = null;
            $setting->enabled = false;
        } elseif (filled($validated['api_key'] ?? null)) {
            $setting->api_key_encrypted = Crypt::encryptString(trim($validated['api_key']));
        }

        $setting->save();

        return back()->withNotify([
            ['success', 'Text-to-speech settings saved. The API key is encrypted and is never sent to users.'],
        ]);
    }
}
