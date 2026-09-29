<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use App\Models\ExtensionPairing;
use Illuminate\Support\Facades\Auth;
use App\Services\FlowAccess;

class DcxExtensionAuthMiddleware
{
    public function handle(Request $request, Closure $next)
    {
        $token = $request->bearerToken();

        if (!$token) {
            return response()->json(['error' => 'Unauthorized'], 401);
        }

        $pairing = ExtensionPairing::with(['user', 'googleFlowAccount'])
            ->where('access_token', hash('sha256', $token))
            ->where('is_active', true)
            ->first();

        if (!$pairing || ($pairing->expires_at && $pairing->expires_at->isPast())) {
            return response()->json(['message' => 'Connection expired or revoked. Connect again.', 'reason' => 'revoked'], 401);
        }

        if (!FlowAccess::eligible($pairing->user)) {
            return response()->json(['message' => 'Your plan is inactive. Contact your administrator.', 'reason' => 'plan_inactive'], 403);
        }

        if ($pairing->user) {
            Auth::setUser($pairing->user);
            $request->setUserResolver(fn () => $pairing->user);
            $request->attributes->set('extension_pairing', $pairing);
        } else {
            return response()->json(['error' => 'User not found'], 401);
        }

        $response = $next($request);
        $response->headers->set('Cache-Control', 'no-store, private');
        return $response;
    }
}
