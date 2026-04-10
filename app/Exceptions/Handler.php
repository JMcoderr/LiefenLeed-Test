<?php

namespace App\Exceptions;

use Illuminate\Support\Facades\Log;
use Throwable;
use Illuminate\Session\TokenMismatchException;
use \Illuminate\Foundation\Exceptions\Handler as ExceptionHandler;
use Inertia\Inertia;
use \Symfony\Component\HttpFoundation\Response;

class Handler extends ExceptionHandler
{
    //
    public function render($request, Throwable $e): Response
    {
        if ($e instanceof TokenMismatchException) {
            if ($request->hasSession()) {
                $request->session()->regenerateToken();
            }
            return Inertia::location(route('login'));
        }

        return parent::render($request, $e);
    }
}
