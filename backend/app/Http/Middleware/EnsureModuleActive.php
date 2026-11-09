<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Services\ModuleLoader;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Checks that a module is active before allowing access to its routes.
 *
 * Usage in routes: ->middleware('module:M01Fleet')
 *
 * Returns 403 with error code "module_inactive" if the module is not active.
 * The actual license check is delegated to the ModuleRegistry (M00Core).
 */
final class EnsureModuleActive
{
    public function __construct(
        private readonly ModuleLoader $loader,
    ) {}

    public function handle(Request $request, Closure $next, string $moduleId): Response
    {
        // M00Core is always active — it's the foundation.
        if ($moduleId === 'M00Core') {
            return $next($request);
        }

        // Check if the module even exists
        if (! $this->loader->exists($moduleId)) {
            return response()->json([
                'error' => [
                    'code'    => 'module_not_found',
                    'message' => "Module [{$moduleId}] is not installed.",
                ],
            ], Response::HTTP_NOT_FOUND);
        }

        // Check license status via the ModuleRegistry service (bound by M00Core)
        if ($this->app()->bound('module.registry')) {
            $registry = $this->app()->make('module.registry');
            if (! $registry->isActive($moduleId)) {
                return response()->json([
                    'error' => [
                        'code'    => 'module_inactive',
                        'message' => "Module [{$moduleId}] is not active or licensed.",
                    ],
                ], Response::HTTP_FORBIDDEN);
            }
        }

        return $next($request);
    }

    private function app(): \Illuminate\Contracts\Foundation\Application
    {
        return app();
    }
}
