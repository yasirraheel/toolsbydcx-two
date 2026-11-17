<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class FlowGuardCors
{
    public function handle(Request $request, Closure $next)
    {
        if ($request->isMethod('OPTIONS')) {
            $response = response('', 204);
        } else {
            $response = $next($request);
        }

        return $response->withHeaders([
            'Access-Control-Allow-Origin' => '*',
            'Access-Control-Allow-Headers' => 'Authorization, Content-Type, Accept, X-Requested-With, X-Flow-Guard-Installation',
            'Access-Control-Allow-Methods' => 'GET, POST, PUT, DELETE, OPTIONS',
            'Cache-Control' => 'no-store, private',
        ]);
    }
}
