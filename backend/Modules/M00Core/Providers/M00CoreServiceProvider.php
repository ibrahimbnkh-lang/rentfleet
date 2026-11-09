<?php

declare(strict_types=1);

namespace Modules\M00Core\Providers;

use Illuminate\Support\ServiceProvider;
use Modules\M00Core\Console\Commands\LicenseIssueCommand;
use Modules\M00Core\Services\AuditService;
use Modules\M00Core\Services\LicenseService;
use Modules\M00Core\Services\ModuleRegistry;
use Modules\M00Core\Services\TwoFactorService;

class M00CoreServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(LicenseService::class);
        $this->app->singleton(AuditService::class);
        $this->app->singleton(TwoFactorService::class);

        $this->app->singleton(ModuleRegistry::class, function ($app) {
            return new ModuleRegistry(
                $app->make(\App\Services\ModuleLoader::class),
                $app->make(LicenseService::class),
            );
        });

        $this->app->alias(ModuleRegistry::class, 'module.registry');
    }

    public function boot(): void
    {
        if ($this->app->runningInConsole()) {
            $this->commands([
                LicenseIssueCommand::class,
            ]);
        }
    }
}
