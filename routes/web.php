<?php

use App\Http\Controllers\CustomerAuthController;
use App\Http\Controllers\CustomerDashboardController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

Route::get('/customer/login', [CustomerAuthController::class, 'showLoginForm'])->name('customer.login');
Route::post('/customer/login', [CustomerAuthController::class, 'requestOtp'])->name('customer.otp.request');
Route::get('/customer/login/verify', [CustomerAuthController::class, 'showOtpForm'])->name('customer.otp.show');
Route::post('/customer/login/verify', [CustomerAuthController::class, 'verifyOtp'])->name('customer.otp.verify');
Route::post('/customer/logout', [CustomerAuthController::class, 'logout'])->name('customer.logout');

Route::get('/customer/dashboard', [CustomerDashboardController::class, 'index'])->name('customer.dashboard');
Route::get('/customer/bill-of-ladings/{billOfLading}', [CustomerDashboardController::class, 'show'])->name('customer.bill-of-ladings.show');
