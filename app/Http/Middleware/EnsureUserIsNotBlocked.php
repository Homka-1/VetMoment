<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

/**
 * Заблокированный пользователь не может пользоваться сайтом: если блокировка случилась
 * во время сессии, на следующем запросе он выходит из аккаунта.
 */
class EnsureUserIsNotBlocked
{
    public function handle(Request $request, Closure $next): Response
    {
        if ($request->user()?->is_blocked) {
            Auth::guard('web')->logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            return redirect()->route('login')->withErrors(['identifier' => __('auth.blocked')]);
        }

        return $next($request);
    }
}
