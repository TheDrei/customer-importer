<?php

use App\Http\Controllers\Api\CustomerController;
use App\Services\RandomUserDataProvider;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('customers');
});

Route::get('/customers', [CustomerController::class, 'index']);
Route::get('/customers/{id}', [CustomerController::class, 'show']);


