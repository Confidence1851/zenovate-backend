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
    Route::resource('users', UserController::class)->only('index');
    // Route::get('payment-receipt', PaymentController::class);
    Route::resource('payments', PaymentController::class);
    Route::resource('applications', ApplicationController::class);

    Route::as("consultation.")->prefix("consultation")->group(function () {
        Route::resource('patients', PatientController::class);
        Route::get('/patients/{id}/print', [PatientController::class, 'print'])->name('patients.print');
        Route::resource('patient-records', PatientRecordController::class);
        Route::get('/patient-records/{id}/print', [PatientRecordController::class, 'print'])->name('patient-records.print');
    });
});

Auth::routes(["register" => false]);

Route::get('/home', function () {
    return redirect()->route("dashboard.index");
})->name('home');
