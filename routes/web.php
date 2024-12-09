<?php

use App\Http\Controllers\Dashboard\AdminController;
use App\Http\Controllers\Dashboard\FormSessionController;
use App\Http\Controllers\Dashboard\HomeController;
use App\Http\Controllers\Dashboard\PaymentController;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return redirect()->route("login");
});

Route::as("dashboard.")->prefix("dashboard")->middleware(["auth", "admin"])->group(function () {
    Route::get('/', [HomeController::class, 'index'])->name('index');
    Route::resource('form-sessions', FormSessionController::class);
    Route::resource('admins', AdminController::class);
    // Route::resource('users', UserController::class)->only('index');
    // Route::get('payment-receipt', PaymentController::class);
    Route::resource('payments', PaymentController::class);
});

Auth::routes(["register" => false]);

Route::get('/home', function () {
    return redirect()->route("dashboard.index");
})->name('home');
