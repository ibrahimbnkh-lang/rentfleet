<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;

/*
 * API Routes — versioned under /api/v1 (set in bootstrap/app.php)
 *
 * All module routes are loaded here. Each module registers its own
 * sub-routes from Modules/{ID}/Routes/api.php.
 */

// Health check (no auth needed)
Route::get('/health', fn () => response()->json([
    'status'    => 'ok',
    'timestamp' => now()->toIso8601String(),
    'timezone'  => config('app.timezone'),
]));

// Dynamically load each module's routes
$modulesPath = base_path('Modules');

if (is_dir($modulesPath)) {
    foreach (glob($modulesPath . '/*/Routes/api.php') as $moduleRouteFile) {
        $moduleDir = dirname($moduleRouteFile, 2);
        if (is_dir($moduleDir . '/Http/Controllers')) {
            require $moduleRouteFile;
        }
    }
}
