<?php

use App\Http\Controllers\PrintController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

// ── Print & Export routes (auth protected) ─────────────────────────────────
Route::middleware(['auth'])->group(function () {
    Route::get('/print/customer/{id}/statement', [PrintController::class, 'customerStatement'])
        ->name('print.customer.statement');

    Route::get('/print/credit-order/{id}', [PrintController::class, 'creditOrderInvoice'])
        ->name('print.credit-order');

    Route::get('/export/customer/{id}/ledger.csv', [PrintController::class, 'exportLedgerCsv'])
        ->name('export.customer.ledger.csv');
});
