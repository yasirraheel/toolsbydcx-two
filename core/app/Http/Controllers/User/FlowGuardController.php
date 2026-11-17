<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use App\Services\FlowGuardAccessService;
use Illuminate\Http\Request;

class FlowGuardController extends Controller
{
    public function session(Request $request, FlowGuardAccessService $access)
    {
        [$session, $token] = $access->issue($request->user(), $request, $request->input('installationId'));
        return response()->json([
            'token' => $token,
            'expires_at' => $session->expires_at->toIso8601String(),
            'status' => $access->payload($session),
        ])->header('Cache-Control', 'no-store, private');
    }

    public function revoke(Request $request)
    {
        \App\Models\FlowGuardSession::where('user_id', $request->user()->id)
            ->whereNull('revoked_at')->update(['revoked_at' => now(), 'updated_at' => now()]);
        return response()->json(['ok' => true]);
    }
}
