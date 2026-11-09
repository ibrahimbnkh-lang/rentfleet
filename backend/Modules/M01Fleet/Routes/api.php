<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;

/*
 * M01Fleet Routes
 */

Route::prefix('fleet')->middleware(['auth:sanctum', 'module:M01Fleet'])->group(function () {

    // ─── Vehicle Categories ──────────────────────────────────────────────
    Route::get('categories', [\Modules\M01Fleet\Http\Controllers\VehicleCategoryController::class, 'index'])->middleware('permission:fleet.category.view');
    Route::post('categories', [\Modules\M01Fleet\Http\Controllers\VehicleCategoryController::class, 'store'])->middleware('permission:fleet.category.create');
    Route::get('categories/{category}', [\Modules\M01Fleet\Http\Controllers\VehicleCategoryController::class, 'show'])->middleware('permission:fleet.category.view');
    Route::put('categories/{category}', [\Modules\M01Fleet\Http\Controllers\VehicleCategoryController::class, 'update'])->middleware('permission:fleet.category.update');
    Route::delete('categories/{category}', [\Modules\M01Fleet\Http\Controllers\VehicleCategoryController::class, 'destroy'])->middleware('permission:fleet.category.delete');

    // ─── Vehicles ────────────────────────────────────────────────────────
    Route::get('vehicles', [\Modules\M01Fleet\Http\Controllers\VehicleController::class, 'index'])->middleware('permission:fleet.vehicle.view');
    Route::post('vehicles', [\Modules\M01Fleet\Http\Controllers\VehicleController::class, 'store'])->middleware('permission:fleet.vehicle.create');
    Route::get('vehicles/{vehicle}', [\Modules\M01Fleet\Http\Controllers\VehicleController::class, 'show'])->middleware('permission:fleet.vehicle.view');
    Route::put('vehicles/{vehicle}', [\Modules\M01Fleet\Http\Controllers\VehicleController::class, 'update'])->middleware('permission:fleet.vehicle.update');
    Route::delete('vehicles/{vehicle}', [\Modules\M01Fleet\Http\Controllers\VehicleController::class, 'destroy'])->middleware('permission:fleet.vehicle.delete');

    Route::prefix('vehicles/{vehicle}')->group(function () {
        Route::get('/photos', [\Modules\M01Fleet\Http\Controllers\VehiclePhotoController::class, 'index'])->middleware('permission:fleet.vehicle.view');
        Route::post('/photos', [\Modules\M01Fleet\Http\Controllers\VehiclePhotoController::class, 'store'])->middleware('permission:fleet.vehicle.update');
        Route::patch('/photos/{photo}/primary', [\Modules\M01Fleet\Http\Controllers\VehiclePhotoController::class, 'setPrimary'])->middleware('permission:fleet.vehicle.update');
        Route::delete('/photos/{photo}', [\Modules\M01Fleet\Http\Controllers\VehiclePhotoController::class, 'destroy'])->middleware('permission:fleet.vehicle.update');

        Route::get('/documents', [\Modules\M01Fleet\Http\Controllers\VehicleDocumentController::class, 'index'])->middleware('permission:fleet.document.view');
        Route::post('/documents', [\Modules\M01Fleet\Http\Controllers\VehicleDocumentController::class, 'store'])->middleware('permission:fleet.document.create');
        Route::put('/documents/{document}', [\Modules\M01Fleet\Http\Controllers\VehicleDocumentController::class, 'update'])->middleware('permission:fleet.document.update');
        Route::delete('/documents/{document}', [\Modules\M01Fleet\Http\Controllers\VehicleDocumentController::class, 'destroy'])->middleware('permission:fleet.document.delete');

        Route::get('/equipment', [\Modules\M01Fleet\Http\Controllers\VehicleEquipmentController::class, 'index'])->middleware('permission:fleet.vehicle.view');
        Route::put('/equipment', [\Modules\M01Fleet\Http\Controllers\VehicleEquipmentController::class, 'update'])->middleware('permission:fleet.vehicle.update');

        Route::get('/rentable', [\Modules\M01Fleet\Http\Controllers\VehicleController::class, 'isRentable'])->middleware('permission:fleet.vehicle.view');
    });

    // ─── Expiring Documents ──────────────────────────────────────────────
    Route::get('expiring-documents', [\Modules\M01Fleet\Http\Controllers\VehicleDocumentController::class, 'expiring'])->middleware('permission:fleet.document.view');
    Route::get('documents/expiring', [\Modules\M01Fleet\Http\Controllers\VehicleDocumentController::class, 'expiring'])->middleware('permission:fleet.document.view');
});

// ─── Public Agency Fleet Showcase (no auth needed) ────────────────────────
Route::prefix('public')->middleware('module:M01Fleet')->group(function () {
    Route::get('/branches/{code}/fleet', [\Modules\M01Fleet\Http\Controllers\VehicleController::class, 'publicAgencyFleet']);
});
