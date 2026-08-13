<?php

use App\Console\Commands\CheckPilotReadiness;
use App\Console\Commands\CleanupPilotRuntime;
use App\Console\Commands\SetupLocalPilotRegion;
use App\Http\Middleware\AddSecurityHeaders;
use App\Http\Middleware\EnsureModuleEnabled;
use App\Http\Middleware\EnsureUserIsKelurahan;
use App\Http\Middleware\EnsureUserIsRt;
use App\Http\Middleware\EnsureUserIsRw;
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
    ->withCommands([
        CheckPilotReadiness::class,
        CleanupPilotRuntime::class,
        SetupLocalPilotRegion::class,
    ])
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->append(AddSecurityHeaders::class);

        $middleware->validateCsrfTokens(except: [
            'webhooks/whatsapp',
        ]);

        $middleware->alias([
            'role.rt' => EnsureUserIsRt::class,
            'role.rw' => EnsureUserIsRw::class,
            'role.kelurahan' => EnsureUserIsKelurahan::class,
            'module' => EnsureModuleEnabled::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->shouldRenderJsonWhen(
            fn (Request $request) => $request->is('api/*'),
        );
    })->create();
