<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Vite;
use Symfony\Component\HttpFoundation\Response;

/**
 * Заголовки безопасности для всех страниц приложения.
 *
 * Загруженные файлы (/storage/*) отдаёт Apache напрямую — для них nosniff и
 * X-Frame-Options выставлены в конфигурации Apache (Dockerfile).
 */
class SecurityHeaders
{
    /**
     * Политика рассчитана на то, что все скрипты и стили — файлы из public/build:
     * inline-скриптов и обработчиков onclick в шаблонах нет. Inline-стили (`style="width: 68%"`
     * у прогресса сборов) разрешены. form-action не ограничен — платёжный провайдер
     * перенаправляет на свою страницу оплаты.
     */
    public const CSP = "default-src 'self'; script-src 'self'; style-src 'self' 'unsafe-inline'; "
        ."img-src 'self' data: blob:; font-src 'self'; connect-src 'self'; object-src 'none'; "
        ."base-uri 'self'; frame-ancestors 'self'";

    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        $headers = $response->headers;
        $headers->set('X-Content-Type-Options', 'nosniff');
        $headers->set('X-Frame-Options', 'SAMEORIGIN');
        $headers->set('Referrer-Policy', 'strict-origin-when-cross-origin');
        $headers->set('Permissions-Policy', 'camera=(), microphone=(), geolocation=(), payment=()');

        if (config('security.csp') && ! Vite::isRunningHot()) {
            $headers->set('Content-Security-Policy', self::CSP);
        }

        if ($request->isSecure()) {
            $headers->set('Strict-Transport-Security', 'max-age='.config('security.hsts_max_age'));
        }

        return $response;
    }
}
