<?php

namespace App\Http\Middleware;

use App\Constants\Status;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class RedirectIfNotReseller
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        if (!auth()->check()) {
            return redirect()->route('user.login');
        }

        $user = auth()->user();

        if (!$user->is_reseller) {
            $notify[] = ['error', 'You do not have access to the Reseller Portal.'];
            return redirect()->route('user.home')->withNotify($notify);
        }

        if ($user->status != Status::USER_ACTIVE) {
            auth()->logout();
            $notify[] = ['error', 'Your reseller account has been suspended. Please contact administrator.'];
            return redirect()->route('user.login')->withNotify($notify);
        }

        return $next($request);
    }
}
