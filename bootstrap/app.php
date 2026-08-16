<?php

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
        // CheckLicense is appended to the web middleware group.
        // Note: AppServiceProvider::verifyApplicationIntegrity() provides a SECOND,
        // independent enforcement point that runs even if this middleware is removed.
        $middleware->web(append: [
            \App\Http\Middleware\UpdateUserActivity::class,
            \App\Http\Middleware\CheckLicense::class,
        ]);

        // Register the feature-level middleware alias.
        // Usage: ->middleware('require-feature:dialer')
        $middleware->alias([
            'require-feature' => \App\Http\Middleware\RequireFeature::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        //
    })->create();
