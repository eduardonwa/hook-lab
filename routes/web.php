<?php

use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

/* Route::view('/billing/success', 'billing.success')
    ->middleware(['auth'])
    ->name('billing.success');

Route::view('/billing/cancel', 'billing.cancel')
    ->middleware(['auth'])
    ->name('billing.cancel'); */