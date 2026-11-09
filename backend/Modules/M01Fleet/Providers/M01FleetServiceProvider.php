<?php

declare(strict_types=1);

namespace Modules\M01Fleet\Providers;

use Illuminate\Support\ServiceProvider;
use Modules\M01Fleet\Services\ComplianceService;

class M01FleetServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(ComplianceService::class);
    }

    public function boot(): void
    {
        //
    }
}
