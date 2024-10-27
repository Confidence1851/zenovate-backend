<?php

use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    // return redirect()->away(env("FRONTEND_APP_URL"));
    return view('welcome');
});

// Route:: as("dashboard.")->prefix("dashboard")->middleware(["auth", "admin"])->group(function () {
//     Route::resource('admins', AdminController::class);
//     Route::resource('users', UserController::class)->only('index');
//     Route::resource('payments', PaymentController::class);
//     Route::resource('applications', ApplicationController::class);

//     Route:: as("consultation.")->prefix("consultation")->group(function () {
//         Route::resource('patients', PatientController::class);
//         Route::get('/patients/{id}/print', [PatientController::class, 'print'])->name('patients.print');
//         Route::resource('patient-records', PatientRecordController::class);
//         Route::get('/patient-records/{id}/print', [PatientRecordController::class, 'print'])->name('patient-records.print');
//     });
// });
