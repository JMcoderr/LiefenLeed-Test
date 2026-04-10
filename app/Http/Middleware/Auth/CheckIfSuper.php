<?php

namespace App\Http\Middleware\Auth;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckIfSuper
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

        if (!$magic['admin']['isSuper'])
            return redirect()->route('admin.requests.index');

        return $next($request);
    }
}
