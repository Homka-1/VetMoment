<?php

use App\Http\Middleware\EnsureUserHasRole;
use App\Http\Middleware\EnsureUserIsNotBlocked;
use App\Http\Middleware\SecurityHeaders;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->alias([
            'role' => EnsureUserHasRole::class,
        ]);

        // Webhook платёжных провайдеров: подлинность проверяется подписью, а не CSRF-токеном.
        $middleware->validateCsrfTokens(except: ['payments/webhook/*']);

        $middleware->web(append: [
            EnsureUserIsNotBlocked::class,
        ]);

        // Глобально, а не в web-группе: заголовки нужны и на 404 неизвестного адреса.
        $middleware->append(SecurityHeaders::class);

        // Гость → вход (с запоминанием intended URL); вошедший на /login → в свой кабинет.
        $middleware->redirectGuestsTo(fn () => route('login'));
        $middleware->redirectUsersTo(fn (Request $request) => route($request->user()->homeRoute()));
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        //
    })->create();
