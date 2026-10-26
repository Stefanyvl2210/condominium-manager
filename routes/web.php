<?php

use App\Http\Controllers\DemoLoginController;
use App\Http\Controllers\InvoicePdfController;
use Illuminate\Support\Facades\Route;

Route::view('/', 'welcome')->name('home');

Route::post('/demo-login/{account}', DemoLoginController::class)
    ->middleware('throttle:20,1')
    ->name('demo.login');

Route::get('/invoices/{invoice}/pdf', InvoicePdfController::class)
    ->middleware('auth')
    ->name('invoices.pdf');
