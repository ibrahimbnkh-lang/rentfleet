<?php

use App\Providers\AppServiceProvider;
use Modules\M00Core\Providers\M00CoreServiceProvider;
use Modules\M01Fleet\Providers\M01FleetServiceProvider;

return [
    AppServiceProvider::class,
    M00CoreServiceProvider::class,
    M01FleetServiceProvider::class,
];
