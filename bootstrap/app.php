<?php

use App\Http\Middleware\EnsureUserIsActive;
use App\Http\Middleware\EnsureUserIsAdmin;
use App\Http\Middleware\EnsureUserIsStudent;
use App\Http\Middleware\EnsureUserIsTeacherOrAdmin;
use App\Http\Middleware\SecurityHeaders;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;
use Illuminate\Routing\Middleware\SubstituteBindings;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->alias([
            'teacher' => EnsureUserIsTeacherOrAdmin::class,
            'admin' => EnsureUserIsAdmin::class,
            'student' => EnsureUserIsStudent::class,
        ]);

        foreach ([EnsureUserIsStudent::class, EnsureUserIsTeacherOrAdmin::class, EnsureUserIsAdmin::class] as $roleMiddleware) {
            $middleware->prependToPriorityList(before: SubstituteBindings::class, prepend: $roleMiddleware);
        }

        // Every response (including /up) is server-hardened with transport
        // security + CSP headers. Global (not web-group) so the middleware
        // also covers JSON/API responses and the health endpoint (US-701).
        $middleware->append(SecurityHeaders::class);

        // Immediate lockout for deactivated accounts: the status check runs on
        // every web request once the session has started (US-701).
        $middleware->web(append: [
            EnsureUserIsActive::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->shouldRenderJsonWhen(
            fn (Request $request) => $request->is('api/*') || $request->expectsJson(),
        );
    })->create();
