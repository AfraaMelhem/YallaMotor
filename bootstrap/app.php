<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Support\Facades\Route;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__ . '/../routes/web.php',
        api: __DIR__ . '/../routes/api.php',
        commands: __DIR__ . '/../routes/console.php',
        health: '/up',
        then: function () {
            Route::middleware('api')
                ->prefix('api/v1/auth')
                ->group(base_path('routes/v1/auth.php'));

            Route::middleware('api')
                ->prefix('api/v1')
                ->group(base_path('routes/v1/rbac.php'));
            Route::middleware('api')
                ->prefix('api/v1/admin')
                ->group(base_path('routes/v1/admin.php'));
                Route::middleware('api')
                ->prefix('api/v1/user')
                ->group(base_path('routes/v1/user.php'));

        },

    )
    ->withMiddleware(function (Middleware $middleware) {
        $middleware->alias([
            'role' => Spatie\Permission\Middleware\RoleMiddleware::class,
            'permission' => Spatie\Permission\Middleware\PermissionMiddleware::class,
            'role_or_permission' => Spatie\Permission\Middleware\RoleOrPermissionMiddleware::class,
            'admin.or.super-admin' => \App\Http\Middleware\AdminOrSuperAdminMiddleware::class,
        ]);
        $middleware->group('api', [
            \App\Http\Middleware\SetLocale::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions) {
        //
    })->create();
