<?php

namespace App\Http\Middleware\Auth;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckIfGuest
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        if (!session()->has('magic'))
            return $next($request);

        $magic = session()->get('magic');
        $expiresAt = data_get($magic, 'expires_at');

        // Expired or malformed session data should not block fresh magic links.
        if (empty($expiresAt) || now()->greaterThan($expiresAt)) {
            session()->forget('magic');
            return $next($request);
        }

        return redirect()->route('admin.requests.index');
    }
}
