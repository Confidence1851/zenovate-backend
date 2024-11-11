<?php

use App\Http\Controllers\Api\FormController;
use Illuminate\Support\Facades\Route;

Route:: as("api.form.")->prefix("form")->group(function () {
    Route::get('/products', [FormController::class, 'productIndex'])->name('products.index');
    Route::get('/session/info/{id}', [FormController::class, 'info'])->name('session.info');
    Route::post('/session/start', [FormController::class, 'startSession'])->name('session.start');
    Route::post('/session/update', [FormController::class, 'updateSession'])->name('session.update');
    Route::any('/session/payment/callback/{payment_id}/{status}', [FormController::class, 'paymentCallback'])->name('session.payment.callback');
    Route::any('/session/webhook', [FormController::class, 'webhookHandler'])->name('session.webhooks');

});
