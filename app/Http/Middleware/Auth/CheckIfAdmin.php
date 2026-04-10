<?php

namespace App\Http\Middleware\Auth;

use Closure;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Symfony\Component\HttpFoundation\Response;

class CheckIfAdmin
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $magic = $request->attributes->get('magic');

        if (is_null($magic))
            return redirect()->route('login');

        if (!$magic['admin']['isAdmin'])
            return redirect()->route('requests');

//        Inertia::share(['isAdmin' => true]);
        return $next($request);
    }
}
