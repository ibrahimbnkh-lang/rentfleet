<?php

declare(strict_types=1);

namespace App\Providers;

use App\Services\ModuleLoader;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        // Register ModuleLoader as singleton
        $this->app->singleton(ModuleLoader::class, function () {
            return new ModuleLoader(base_path('Modules'));
        });
    }

    public function boot(): void
    {
        // Implicitly grant "Super Admin" role all permissions
        Gate::before(function ($user, $ability) {
            return $user->hasRole('Super Admin') ? true : null;
        });

        // Auto-discover migrations from all modules
        $modulesPath = base_path('Modules');
        if (is_dir($modulesPath)) {
            foreach (glob($modulesPath . '/*/Database/Migrations') as $migrationPath) {
                $this->loadMigrationsFrom($migrationPath);
            }
        }

        // Validate module prerequisites at boot time in non-production
        if ($this->app->environment('local', 'testing')) {
            /** @var ModuleLoader $loader */
            $loader = $this->app->make(ModuleLoader::class);
            $loader->validatePrerequisites();
        }
    }
}
