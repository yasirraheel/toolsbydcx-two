<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\UserChatThread;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class ChatHistoryController extends Controller
{
    private const PROVIDERS = ['chatgpt'];

    public function __construct()
    {
        $origin = (string) request()->header('Origin', '');
        if (str_starts_with($origin, 'chrome-extension://') || str_starts_with($origin, 'moz-extension://') || str_contains($origin, 'toolsbydcx.com')) {
            header('Access-Control-Allow-Origin: ' . $origin);
            header('Access-Control-Allow-Credentials: true');
        }
        header('Access-Control-Allow-Headers: Content-Type, X-Requested-With, Accept');
        header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
    }

    private function provider(Request $request): string
    {
        $provider = strtolower((string) $request->input('provider', $request->query('provider', 'chatgpt')));
        abort_unless(in_array($provider, self::PROVIDERS, true), 422, 'Unsupported chat provider.');

        return $provider;
    }

    public function index(Request $request)
    {
        $user = $request->user();
        if (!$user) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 401);
        }

        $provider = $this->provider($request);
        $chats = UserChatThread::query()
            ->where('user_id', $user->id)
            ->where('provider', $provider)
            ->orderByDesc('last_seen_at')
            ->orderByDesc('id')
            ->limit(500)
            ->get(['chat_id', 'title', 'last_seen_at'])
            ->unique('chat_id')
            ->map(fn (UserChatThread $chat) => [
                'id' => $chat->chat_id,
                'title' => $chat->title,
                'lastSeenAt' => optional($chat->last_seen_at)->toIso8601String(),
                'hasArchive' => (bool) $chat->messages_encrypted,
                'messageCount' => (int) $chat->message_count,
            ])
            ->values();

        return response()->json([
            'success' => true,
            'provider' => $provider,
            'chats' => $chats,
        ])->header('Cache-Control', 'no-store, no-cache, must-revalidate, max-age=0');
    }

    public function store(Request $request)
    {
        $user = $request->user();
        if (!$user) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 401);
        }

        $validated = $request->validate([
            'chat_id' => ['required', 'string', 'max:128', 'regex:/^[A-Za-z0-9_-]{4,128}$/'],
            'title' => ['nullable', 'string', 'max:300'],
        ]);
        $provider = $this->provider($request);
        $now = now();

        $chat = UserChatThread::updateOrCreate(
            [
                'user_id' => $user->id,
                'provider' => $provider,
                'chat_id' => $validated['chat_id'],
            ],
            [
                'title' => isset($validated['title']) ? trim($validated['title']) : null,
                'last_seen_at' => $now,
            ]
        );

        return response()->json([
            'success' => true,
            'chat' => [
                'id' => $chat->chat_id,
                'title' => $chat->title,
                'lastSeenAt' => optional($chat->last_seen_at)->toIso8601String(),
            ],
        ]);
    }

    public function archive(Request $request)
    {
        $user = $request->user();
        if (!$user) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 401);
        }

        $validated = $request->validate([
            'chat_id' => ['required', 'string', 'max:128', 'regex:/^[A-Za-z0-9_-]{4,128}$/'],
            'title' => ['nullable', 'string', 'max:300'],
            'messages' => ['required', 'array', 'min:1', 'max:200'],
            'messages.*.role' => ['required', 'string', 'in:user,assistant,system,tool'],
            'messages.*.content' => ['required', 'string', 'max:100000'],
        ]);

        $provider = $this->provider($request);
        $chat = UserChatThread::firstOrNew([
            'user_id' => $user->id,
            'provider' => $provider,
            'chat_id' => $validated['chat_id'],
        ]);

        $chat->title = isset($validated['title']) ? trim($validated['title']) : ($chat->title ?: null);
        $chat->last_seen_at = now();

        // A partially rendered DOM must never replace a more complete archive.
        if (!$chat->messages_encrypted || count($validated['messages']) >= (int) $chat->message_count) {
            $chat->archiveMessages(array_values(array_map(static function (array $message): array {
                return [
                    'role' => $message['role'],
                    'content' => trim($message['content']),
                ];
            }, $validated['messages'])));
        }

        $chat->save();

        return response()->json([
            'success' => true,
            'chat' => [
                'id' => $chat->chat_id,
                'title' => $chat->title,
                'messageCount' => (int) $chat->message_count,
                'lastSeenAt' => optional($chat->last_seen_at)->toIso8601String(),
            ],
        ]);
    }

    public function share(Request $request)
    {
        $user = $request->user();
        if (!$user) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 401);
        }

        $validated = $request->validate([
            'chat_id' => ['required', 'string', 'max:128', 'regex:/^[A-Za-z0-9_-]{4,128}$/'],
            'public' => ['required', 'boolean'],
        ]);

        $chat = UserChatThread::where('user_id', $user->id)
            ->where('provider', 'chatgpt')
            ->where('chat_id', $validated['chat_id'])
            ->firstOrFail();

        if ($validated['public']) {
            $chat->share_token = $chat->share_token ?: Str::random(48);
            $chat->is_public = true;
        } else {
            $chat->share_token = null;
            $chat->is_public = false;
        }
        $chat->save();

        return response()->json([
            'success' => true,
            'public' => (bool) $chat->is_public,
            'url' => $chat->is_public ? route('chat.share', $chat->share_token) : null,
        ]);
    }
}
