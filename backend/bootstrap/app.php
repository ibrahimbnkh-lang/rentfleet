<?php

declare(strict_types=1);

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\HttpKernel\Exception\MethodNotAllowedHttpException;
use Spatie\Permission\Exceptions\UnauthorizedException;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        api: __DIR__ . '/../routes/api.php',
        apiPrefix: 'api/v1',
        commands: __DIR__ . '/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware) {
        $middleware->alias([
            'module'             => \App\Http\Middleware\EnsureModuleActive::class,
            'permission'         => \Spatie\Permission\Middleware\PermissionMiddleware::class,
            'role'               => \Spatie\Permission\Middleware\RoleMiddleware::class,
            'role_or_permission' => \Spatie\Permission\Middleware\RoleOrPermissionMiddleware::class,
        ]);

        // Always return 401 JSON for unauthenticated API requests (no web login redirect)
        $middleware->redirectGuestsTo(fn () => null);
    })
    ->withExceptions(function (Exceptions $exceptions) {
        // Return JSON for all API exceptions
        $exceptions->render(function (\Throwable $e, Request $request) {
            if (! $request->expectsJson() && ! $request->is('api/*')) {
                return null; // Let default handler deal with web routes
            }

            if ($e instanceof ValidationException) {
                return response()->json([
                    'error' => [
                        'code'    => 'validation_failed',
                        'message' => $e->getMessage(),
                        'details' => $e->errors(),
                    ],
                ], Response::HTTP_UNPROCESSABLE_ENTITY);
            }

            if ($e instanceof AuthenticationException) {
                return response()->json([
                    'error' => [
                        'code'    => 'unauthenticated',
                        'message' => 'You are not authenticated.',
                    ],
                ], Response::HTTP_UNAUTHORIZED);
            }

            if ($e instanceof UnauthorizedException) {
                return response()->json([
                    'error' => [
                        'code'    => 'forbidden',
                        'message' => 'You do not have the required permissions.',
                    ],
                ], Response::HTTP_FORBIDDEN);
            }

            if ($e instanceof ModelNotFoundException || $e instanceof NotFoundHttpException) {
                return response()->json([
                    'error' => [
                        'code'    => 'not_found',
                        'message' => 'The requested resource was not found.',
                    ],
                ], Response::HTTP_NOT_FOUND);
            }

            if ($e instanceof MethodNotAllowedHttpException) {
                return response()->json([
                    'error' => [
                        'code'    => 'method_not_allowed',
                        'message' => 'HTTP method not allowed.',
                    ],
                ], Response::HTTP_METHOD_NOT_ALLOWED);
            }

            // Generic server error
            $debug = config('app.debug');

            return response()->json([
                'error' => [
                    'code'    => 'server_error',
                    'message' => $debug ? $e->getMessage() : 'An unexpected error occurred.',
                    'trace'   => $debug ? collect($e->getTrace())->take(5)->toArray() : [],
                ],
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        });
    })
    ->create();
