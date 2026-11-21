<?php

use App\Http\Middleware\EnsureAdminTwoFactor;
use App\Http\Middleware\EnsureUserHasRole;
use App\Http\Middleware\PreventSensitiveCaching;
use App\Http\Middleware\SecurityHeaders;
use App\Http\Middleware\ShareRestrictedNotifications;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->append(SecurityHeaders::class);
        $middleware->alias([
            'role' => EnsureUserHasRole::class,
            'admin.2fa' => EnsureAdminTwoFactor::class,
            'no.store' => PreventSensitiveCaching::class,
            'restricted.notifications' => ShareRestrictedNotifications::class,
        ]);
        $middleware->redirectGuestsTo('/login');
        $middleware->redirectUsersTo('/area-restrita');
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->dontFlash([
            'current_password',
            'password',
            'password_confirmation',
            'code',
            'recovery_code',
            'two_factor_secret',
            'token',
        ]);
    })->create();
