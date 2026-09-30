<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware) {
        $middleware->alias([
            'cognito.jwt' => \App\Http\Middleware\CognitoJwtMiddleware::class,
            'admin' => \App\Http\Middleware\EnsureAdmin::class,
            'organization' => \App\Http\Middleware\EnsureOrganization::class,
        ]);

        $middleware->web(append: [
            \App\Http\Middleware\RefreshCognitoSession::class,
        ]);

        $middleware->redirectUsersTo(function (\Illuminate\Http\Request $request) {
            $user = $request->user();

            return $user instanceof \App\Models\AppUser
                ? \App\Http\Controllers\Auth\AuthenticatedSessionController::homeUrl($user)
                : '/';
        });
    })
    ->withExceptions(function (Exceptions $exceptions) {
        //
    })->create();
