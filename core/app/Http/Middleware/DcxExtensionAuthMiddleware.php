<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use App\Models\ExtensionPairing;
use Illuminate\Support\Facades\Auth;

class DcxExtensionAuthMiddleware
{
    public function handle(Request $request, Closure $next)
    {
        $token = $request->bearerToken();

        if (!$token) {
            return response()->json(['error' => 'Unauthorized'], 401);
        }

        $pairing = ExtensionPairing::with('user')
            ->where('access_token', $token)
            ->where('is_active', true)
            ->first();

        if (!$pairing || ($pairing->expires_at && $pairing->expires_at->isPast())) {
            return response()->json(['error' => 'Unauthorized or token expired'], 401);
        }

        if ($pairing->user) {
            Auth::setUser($pairing->user);
            $request->attributes->set('extension_pairing', $pairing);
        } else {
            return response()->json(['error' => 'User not found'], 401);
        }

        return $next($request);
    }
}
