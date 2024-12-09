<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\DashboardController;
use App\Http\Controllers\Api\FormController;
use App\Http\Controllers\Api\WebsiteController;
use Illuminate\Support\Facades\Route;

Route:: as("api.")->group(function () {

    Route::get('/get-file/{hash}', [WebsiteController::class, 'getFile'])->name("get-file");


    Route::prefix('auth')->group(function () {
        Route::post('/register', [AuthController::class, 'register']);
        Route::post('/login', [AuthController::class, 'login']);
        Route::post('/authenticate', [AuthController::class, 'authenticate']);
        Route::get('/me', [AuthController::class, 'me'])->middleware("auth:sanctum");
        Route::post('/forgot-password', [AuthController::class, 'forgotPassword']);
        Route::post('/reset-password', [AuthController::class, 'resetPassword']);
    });

    Route::prefix('dashboard')->middleware("auth:sanctum")->group(function () {
        Route::get('/orders', [DashboardController::class, 'orders']);
    });


    Route::prefix('website')->group(function () {
        Route::post('/contact-us', [WebsiteController::class, 'contactUs']);
        Route::post('/newsletter-subscribe', [WebsiteController::class, 'newsletterSubscriber']);
    });

    Route:: as("form.")->prefix("form")->group(function () {
        Route::get('/products', [FormController::class, 'productIndex'])->name('products.index');
        Route::get('/products/{id}', [FormController::class, 'productInfo'])->name('products.info');
        Route::get('/session/info/{id}', [FormController::class, 'info'])->name('session.info');
        Route::post('/session/start', [FormController::class, 'startSession'])->name('session.start');
        Route::post('/session/update', [FormController::class, 'updateSession'])->name('session.update');
        Route::any('/session/payment/callback/{payment_id}/{status}', [FormController::class, 'paymentCallback'])->name('session.payment.callback');
        Route::any('/session/webhook', [FormController::class, 'webhookHandler'])->name('session.webhooks');
    });
});
