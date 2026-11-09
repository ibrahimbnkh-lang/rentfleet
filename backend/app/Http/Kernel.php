<?php

declare(strict_types=1);

namespace App\Http;

use Illuminate\Foundation\Http\Kernel as HttpKernel;

class Kernel extends HttpKernel
{
    /** @var array<string, class-string> */
    protected $routeMiddleware = [
        'auth'              => \Illuminate\Auth\Middleware\Authenticate::class,
        'auth.basic'        => \Illuminate\Auth\Middleware\AuthenticateWithBasicAuth::class,
        'cache.headers'     => \Illuminate\Http\Middleware\SetCacheHeaders::class,
        'can'               => \Illuminate\Auth\Middleware\Authorize::class,
        'guest'             => \Illuminate\Auth\Middleware\RedirectIfAuthenticated::class,
        'module'            => \App\Http\Middleware\EnsureModuleActive::class,
        'permission'        => \Spatie\LaravelPermission\Middlewares\PermissionMiddleware::class,
        'role'              => \Spatie\LaravelPermission\Middlewares\RoleMiddleware::class,
        'role_or_permission' => \Spatie\LaravelPermission\Middlewares\RoleOrPermissionMiddleware::class,
        'throttle'          => \Illuminate\Routing\Middleware\ThrottleRequests::class,
        'verified'          => \Illuminate\Auth\Middleware\EnsureEmailIsVerified::class,
    ];
}
