<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use Modules\M03Reservations\Http\Controllers\ReservationController;

/*
 * M03Reservations Routes
 */

Route::prefix('reservations')->middleware(['auth:sanctum', 'module:M03Reservations'])->group(function () {

    // Availability & Timeline planning
    Route::get('/availability', [ReservationController::class, 'checkAvailability'])->middleware('permission:reservations.reservation.view');
    Route::get('/timeline', [ReservationController::class, 'timeline'])->middleware('permission:reservations.planning.view');

    // Lifecycle state transitions
    Route::post('/{reservation}/confirm', [ReservationController::class, 'confirm'])->middleware('permission:reservations.reservation.confirm');
    Route::post('/{reservation}/start', [ReservationController::class, 'start'])->middleware('permission:reservations.reservation.start');
    Route::post('/{reservation}/complete', [ReservationController::class, 'complete'])->middleware('permission:reservations.reservation.complete');
    Route::post('/{reservation}/cancel', [ReservationController::class, 'cancel'])->middleware('permission:reservations.reservation.cancel');

    // CRUD
    Route::get('/', [ReservationController::class, 'index'])->middleware('permission:reservations.reservation.view');
    Route::post('/', [ReservationController::class, 'store'])->middleware('permission:reservations.reservation.create');
    Route::get('/{reservation}', [ReservationController::class, 'show'])->middleware('permission:reservations.reservation.view');
    Route::put('/{reservation}', [ReservationController::class, 'update'])->middleware('permission:reservations.reservation.update');
    Route::delete('/{reservation}', [ReservationController::class, 'destroy'])->middleware('permission:reservations.reservation.delete');
});

