<?php

namespace App\Http\Middleware\Auth;

use Closure;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Symfony\Component\HttpFoundation\Response;

class CheckIfSessionMagic
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $magic = session()->get('magic', null);

        if (is_null($magic) || now()->greaterThan($magic['expires_at']))
        {
            session()->forget('magic');
            return redirect()->route('login');
        }

        $request->attributes->add(['magic' => $magic]);
//        Inertia::share(['user' => $magic['email']]);
        return $next($request);
    }
}
