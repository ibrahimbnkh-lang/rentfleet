<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use Modules\M05Contracts\Http\Controllers\ContractController;

/*
 * M05Contracts Routes
 */

Route::prefix('contracts')->middleware(['auth:sanctum', 'module:M05Contracts'])->group(function () {

    // E-signature & Check-out actions
    Route::post('/{contract}/sign', [ContractController::class, 'sign'])->middleware('permission:contracts.contract.sign');
    Route::post('/{contract}/checkout', [ContractController::class, 'checkout'])->middleware('permission:contracts.contract.checkout');
    Route::post('/from-reservation/{reservation}', [ContractController::class, 'createFromReservation'])->middleware('permission:contracts.contract.create');

    // CRUD
    Route::get('/', [ContractController::class, 'index'])->middleware('permission:contracts.contract.view');
    Route::post('/', [ContractController::class, 'store'])->middleware('permission:contracts.contract.create');
    Route::get('/{contract}', [ContractController::class, 'show'])->middleware('permission:contracts.contract.view');
    Route::put('/{contract}', [ContractController::class, 'update'])->middleware('permission:contracts.contract.update');
    Route::delete('/{contract}', [ContractController::class, 'destroy'])->middleware('permission:contracts.contract.delete');
});

