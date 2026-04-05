<?php

use Illuminate\Support\Facades\Route;
use Modules\Suppliers\Http\Controllers\SuppliersController;

Route::middleware(['auth', 'verified'])->group(function () {
    Route::resource('suppliers', SuppliersController::class)->names('suppliers');
});
