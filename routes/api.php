<?php

use App\Http\Controllers\Api\FormController;
use Illuminate\Support\Facades\Route;

Route:: as("form.")->prefix("form")->group(function () {
    Route::post('/session/start', [FormController::class, 'startSession'])->name('session.start');
    Route::post('/session/update', [FormController::class, 'updateSession'])->name('session.update');
    Route::post('/session/complete', [FormController::class, 'completeSession'])->name('session.complete');
});
