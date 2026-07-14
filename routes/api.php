<?php

use App\Http\Controllers\Api\AppointmentController;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\CategoryController;
use App\Http\Controllers\Api\ClientController;
use App\Http\Controllers\Api\ClientMeasurementController;
use App\Http\Controllers\Api\DashboardController;
use App\Http\Controllers\Api\DesignController;
use App\Http\Controllers\Api\GalleryController;
use App\Http\Controllers\Api\GarmentTypeController;
use App\Http\Controllers\Api\OrderController;
use App\Http\Controllers\Api\StitchingSizeController;
use App\Http\Controllers\Api\SettingsController;
use App\Http\Controllers\Api\TransactionController;
use App\Http\Controllers\Api\VoiceMeasurementController;
use Illuminate\Support\Facades\Route;

use Illuminate\Support\Facades\Artisan;

Route::get('/run-migration-magic', function () {
    Artisan::call('migrate:fresh --seed');
    return "Database tables created successfully!";
});

Route::post('/appointments', [AppointmentController::class, 'store']);

Route::prefix('auth')->group(function () {
    Route::post('/register', [AuthController::class, 'register']);
    Route::post('/login', [AuthController::class, 'login']);
});

Route::middleware('auth:sanctum')->group(function () {
    Route::prefix('auth')->group(function () {
        Route::post('/logout', [AuthController::class, 'logout']);
        Route::get('/me', [AuthController::class, 'me']);
        Route::put('/profile', [AuthController::class, 'updateProfile']);
    });

    Route::get('/dashboard', [DashboardController::class, 'index']);

    Route::get('/clients/export', [ClientController::class, 'export']);
    Route::apiResource('clients', ClientController::class);
    Route::apiResource('clients.measurements', ClientMeasurementController::class);

    Route::apiResource('garment-types', GarmentTypeController::class)->except(['show']);
    Route::apiResource('designs', DesignController::class);
    Route::apiResource('categories', CategoryController::class)->except(['show']);
    Route::apiResource('gallery', GalleryController::class)->except(['show']);

    Route::apiResource('orders', OrderController::class);
    Route::post('orders/{order}/payment', [OrderController::class, 'recordPayment']);
    Route::patch('orders/{order}/payment-status', [OrderController::class, 'updatePaymentStatus']);

    Route::apiResource('transactions', TransactionController::class)->except(['show']);

    Route::get('/stitching-sizes/presets', [StitchingSizeController::class, 'presets']);
    Route::apiResource('stitching-sizes', StitchingSizeController::class);

    Route::post('/voice-measurements/parse', [VoiceMeasurementController::class, 'parse']);
    Route::post('/voice-measurements', [VoiceMeasurementController::class, 'store']);

    Route::get('/settings', [SettingsController::class, 'show']);
    Route::post('/settings', [SettingsController::class, 'update']);
    Route::put('/settings', [SettingsController::class, 'update']);
});
