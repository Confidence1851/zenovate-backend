<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\CategoryController;
use App\Http\Controllers\Api\DashboardController;
use App\Http\Controllers\Api\FormController;
use App\Http\Controllers\Api\WebsiteController;
use Illuminate\Support\Facades\Route;

Route::as("api.")->group(function () {

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
        Route::get('/orders/{id}', [DashboardController::class, 'orderInfo']);
    });


    Route::prefix('website')->group(function () {
        Route::post('/contact-us', [WebsiteController::class, 'contactUs']);
        Route::post('/newsletter-subscribe', [WebsiteController::class, 'newsletterSubscriber']);
    });

    Route::as("form.")->prefix("form")->group(function () {
        Route::get('/products', [FormController::class, 'productIndex'])->name('products.index');
        Route::get('/products/by-categories', [FormController::class, 'productsByCategories'])->name('products.by-categories');
        Route::get('/products/order-sheet', [FormController::class, 'orderSheetProducts'])->name('products.order-sheet');
        Route::get('/products/{id}', [FormController::class, 'productInfo'])->name('products.info');
        Route::get('/checkout/config', [FormController::class, 'checkoutConfig'])->name('checkout.config');
        Route::get('/session/info/{id}', [FormController::class, 'info'])->name('session.info');
        Route::post('/session/recreate/{id}', [FormController::class, 'recreate'])->name('session.recreate')->middleware("auth:sanctum");
        Route::post('/session/start', [FormController::class, 'startSession'])->name('session.start');
        Route::post('/session/update', [FormController::class, 'updateSession'])->name('session.update');
        Route::any('/session/payment/callback/{payment_id}/{status}', [FormController::class, 'paymentCallback'])->name('session.payment.callback');
        Route::any('/session/webhook', [FormController::class, 'webhookHandler'])->name('session.webhooks');
    });

    Route::prefix('categories')->group(function () {
        Route::get('/', [CategoryController::class, 'index'])->name('categories.index');
        Route::get('/{slug}', [CategoryController::class, 'show'])->name('categories.show');
        Route::get('/{slug}/products', [CategoryController::class, 'products'])->name('categories.products');
    });

    Route::prefix('direct-checkout')
        ->controller(\App\Http\Controllers\Api\DirectCheckoutController::class)
        ->group(function () {
            Route::post('/init', 'init')->name('direct-checkout.init');
            Route::post('/calculate-totals', 'calculateTotals')->name('direct-checkout.calculate-totals');
            Route::post('/order-sheet/init', 'orderSheetInit')->name('direct-checkout.order-sheet.init');
            Route::post('/order-sheet/calculate-totals', 'calculateOrderSheetTotals')->name('direct-checkout.order-sheet.calculate-totals');
            Route::post('/order-sheet/process', 'processOrderSheet')->name('direct-checkout.order-sheet.process');
            Route::post('/cart/init', 'cartInit')->name('direct-checkout.cart.init');
            Route::post('/cart/process', 'processCart')->name('direct-checkout.cart.process');
            Route::post('/process', 'process')->name('direct-checkout.process');
            Route::get('/checkout/info', 'checkoutInfo')->name('direct-checkout.checkout-info');
            Route::post('/cart/summary', 'cartSummary')->name('direct-checkout.cart-summary');
        });
});
