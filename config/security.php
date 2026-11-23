<?php

/*
| Заголовки безопасности ответа (App\Http\Middleware\SecurityHeaders, этап 14).
*/

return [

    // Content-Security-Policy: по умолчанию включена в production. В разработке выключена —
    // Vite dev server (:5173) и витрина UI-кита используют внешние скрипты и inline-обработчики.
    'csp' => (bool) env('SECURITY_CSP', env('APP_ENV') === 'production'),

    // Strict-Transport-Security отдаётся только на HTTPS-запросах (за прокси — см. TRUSTED_PROXIES).
    'hsts_max_age' => (int) env('SECURITY_HSTS_MAX_AGE', 31536000),

    // Адреса прокси/балансировщика, которым доверяем X-Forwarded-* (например, «*» за Caddy/nginx
    // в том же docker-compose). Пусто — не доверяем никому (запуск на localhost).
    'trusted_proxies' => env('TRUSTED_PROXIES'),

];
