<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use Modules\M07Invoicing\Http\Controllers\InvoiceController;
use Modules\M07Invoicing\Http\Controllers\PaymentController;

/*
 * M07Invoicing Routes
 */

Route::prefix('invoicing')->middleware(['auth:sanctum', 'module:M07Invoicing'])->group(function () {

    // Payments / Encaissements
    Route::get('/payments', [PaymentController::class, 'index'])->middleware('permission:invoicing.payment.view');
    Route::get('/payments/{payment}', [PaymentController::class, 'show'])->middleware('permission:invoicing.payment.view');

    // Invoices / Factures
    Route::post('/invoices/from-contract/{contract}', [InvoiceController::class, 'fromContract'])->middleware('permission:invoicing.invoice.create');
    Route::post('/invoices/{invoice}/payments', [InvoiceController::class, 'recordPayment'])->middleware('permission:invoicing.payment.create');
    Route::post('/invoices/{invoice}/cancel', [InvoiceController::class, 'cancel'])->middleware('permission:invoicing.invoice.cancel');

    Route::get('/invoices', [InvoiceController::class, 'index'])->middleware('permission:invoicing.invoice.view');
    Route::post('/invoices', [InvoiceController::class, 'store'])->middleware('permission:invoicing.invoice.create');
    Route::get('/invoices/{invoice}', [InvoiceController::class, 'show'])->middleware('permission:invoicing.invoice.view');
    Route::delete('/invoices/{invoice}', [InvoiceController::class, 'destroy'])->middleware('permission:invoicing.invoice.delete');
});

