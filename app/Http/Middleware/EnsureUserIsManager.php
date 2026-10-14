<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Страницы управляющего в кабинете (смены команды).
 */
class EnsureUserIsManager
{
    public function handle(Request $request, Closure $next): Response
    {
        abort_unless($request->user()?->isManager(), 403);

        return $next($request);
    }
}
