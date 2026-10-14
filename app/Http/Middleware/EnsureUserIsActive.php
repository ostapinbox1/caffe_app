<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

/**
 * Отключённого сотрудника выкидывает из уже открытой сессии
 * (вход по паролю закрыт в FortifyServiceProvider, но есть ещё passkey и «запомнить меня»).
 */
class EnsureUserIsActive
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if ($user !== null && ! $user->is_active) {
            Auth::guard('web')->logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            return redirect()->route('login')->withErrors([
                'email' => 'Доступ отключён. Обратитесь к управляющему.',
            ]);
        }

        return $next($request);
    }
}
