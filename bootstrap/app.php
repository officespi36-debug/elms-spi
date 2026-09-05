<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withCommands([
        __DIR__.'/../app/Console/Commands',
    ])
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->trustProxies(at: '*');
        $middleware->web(append: [\App\Http\Middleware\HandleInertiaRequests::class]);
        $middleware->alias(['role' => \App\Http\Middleware\EnsureRole::class]);
        $middleware->preventRequestForgery(except: [
            'auth/telegram',
            'auth/telegram/*',
            'api/auth/telegram',
            'api/auth/telegram/*',
            'api/telegram/*',
            'api/telegram-webhook',
            'api/webhook/telegram',
            'telegram/*',
            'telegram-webhook',
            'webhook/telegram',
            'auth/clerk',
            'auth/clerk/*',
            'auth/google',
            'auth/google/*',
            'auth/email-otp/*',
            'auth/email-otp',
            'api/auth/email-otp/*',
            'api/auth/email-otp',
            'auth/phone-otp/*',
            'auth/phone-otp',
            'api/auth/phone-otp/*',
            'api/auth/phone-otp',
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->shouldRenderJsonWhen(
            fn (Request $request) => $request->is('api/*'),
        );
    })->create();
