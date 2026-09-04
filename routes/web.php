<?php

use App\Http\Controllers\CustomerAuthController;
use App\Http\Controllers\CustomerDashboardController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

Route::get('/customer/login', [CustomerAuthController::class, 'showLoginForm'])->name('customer.login');
Route::post('/customer/login', [CustomerAuthController::class, 'requestOtp'])
    ->middleware('throttle:5,1')
    ->name('customer.otp.request');
Route::get('/customer/login/verify', [CustomerAuthController::class, 'showOtpForm'])->name('customer.otp.show');
Route::post('/customer/login/verify', [CustomerAuthController::class, 'verifyOtp'])
    ->middleware('throttle:10,1')
    ->name('customer.otp.verify');
Route::post('/customer/logout', [CustomerAuthController::class, 'logout'])->name('customer.logout');

Route::get('/customer/dashboard', [CustomerDashboardController::class, 'index'])->name('customer.dashboard');
Route::get('/customer/bill-of-ladings/{billOfLading}', [CustomerDashboardController::class, 'show'])->name('customer.bill-of-ladings.show');
Route::get('/customer/bill-of-ladings/{billOfLading}/containers/{container}', [CustomerDashboardController::class, 'showContainer'])
    ->name('customer.containers.show');
