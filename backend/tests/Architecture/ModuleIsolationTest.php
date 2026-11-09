<?php

declare(strict_types=1);

/*
|--------------------------------------------------------------------------
| Architecture Tests
|--------------------------------------------------------------------------
|
| These tests enforce the modular monolith rule:
|   A module must NEVER import classes from another module's internal
|   namespaces. Modules communicate only through:
|     - Public service interfaces in App\Contracts\
|     - Domain events in App\Events\
|     - The ModuleLoader singleton
|
*/

arch('modules do not cross-import internals')
    ->expect('Modules\M00Core')
    ->not->toUse(['Modules\M01Fleet', 'Modules\M02Customers']);

arch('M01Fleet does not import M02Customers internals')
    ->expect('Modules\M01Fleet')
    ->not->toUse('Modules\M02Customers');

arch('M02Customers does not import M01Fleet internals')
    ->expect('Modules\M02Customers')
    ->not->toUse('Modules\M01Fleet');

arch('controllers extend ApiController')
    ->expect([
        'Modules\M00Core\Http\Controllers',
        'Modules\M01Fleet\Http\Controllers',
    ])
    ->toExtend(\App\Http\Controllers\ApiController::class);

arch('no debug functions in production code')
    ->expect(['Modules', 'App'])
    ->not->toUse(['dd', 'dump', 'ray', 'var_dump', 'print_r']);
