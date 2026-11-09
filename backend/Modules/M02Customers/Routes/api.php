<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use Modules\M02Customers\Http\Controllers\BlacklistController;
use Modules\M02Customers\Http\Controllers\CustomerController;
use Modules\M02Customers\Http\Controllers\CustomerDocumentController;
use Modules\M02Customers\Http\Controllers\KycController;

/*
 * M02Customers Routes
 */

Route::prefix('customers')->middleware(['auth:sanctum', 'module:M02Customers'])->group(function () {

    // Blacklist global index
    Route::get('/blacklist', [BlacklistController::class, 'index'])->middleware('permission:customers.blacklist.view');

    // Customer CRUD
    Route::get('/', [CustomerController::class, 'index'])->middleware('permission:customers.customer.view');
    Route::post('/', [CustomerController::class, 'store'])->middleware('permission:customers.customer.create');
    Route::get('/{customer}', [CustomerController::class, 'show'])->middleware('permission:customers.customer.view');
    Route::put('/{customer}', [CustomerController::class, 'update'])->middleware('permission:customers.customer.update');
    Route::delete('/{customer}', [CustomerController::class, 'destroy'])->middleware('permission:customers.customer.delete');

    // Customer documents
    Route::get('/{customer}/documents', [CustomerDocumentController::class, 'index'])->middleware('permission:customers.document.view');
    Route::post('/{customer}/documents', [CustomerDocumentController::class, 'store'])->middleware('permission:customers.document.create');
    Route::delete('/{customer}/documents/{document}', [CustomerDocumentController::class, 'destroy'])->middleware('permission:customers.document.delete');

    // Customer KYC
    Route::get('/{customer}/kyc', [KycController::class, 'show'])->middleware('permission:customers.kyc.view');
    Route::post('/{customer}/kyc/verify', [KycController::class, 'verify'])->middleware('permission:customers.kyc.verify');
    Route::post('/{customer}/kyc/reject', [KycController::class, 'reject'])->middleware('permission:customers.kyc.reject');

    // Customer Blacklist per-customer
    Route::get('/{customer}/blacklist', [BlacklistController::class, 'show'])->middleware('permission:customers.blacklist.view');
    Route::post('/{customer}/blacklist', [BlacklistController::class, 'add'])->middleware('permission:customers.blacklist.create');
    Route::delete('/{customer}/blacklist', [BlacklistController::class, 'remove'])->middleware('permission:customers.blacklist.remove');

    // Rental Eligibility evaluation
    Route::get('/{customer}/eligible', [CustomerController::class, 'isEligible'])->middleware('permission:customers.customer.view');
});
