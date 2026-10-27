<?php

use Illuminate\Support\Facades\Route;
use Workbench\App\Http\Controllers\Api\CategoryController;
use Workbench\App\Http\Controllers\Api\MiscController;
use Workbench\App\Http\Controllers\Api\OrderController;
use Workbench\App\Http\Controllers\Api\ProductController;

// Public.
Route::get('/health', [MiscController::class, 'health']);
Route::get('/categories', [CategoryController::class, 'index']);
Route::get('/products', [ProductController::class, 'index']);
Route::get('/products/{product}', [ProductController::class, 'show']);

// Any valid bearer token.
Route::middleware('workbench.token')->group(function () {
    Route::get('/me', [MiscController::class, 'me']);
    Route::post('/products', [ProductController::class, 'store']);
    Route::put('/products/{product}', [ProductController::class, 'update']);
    Route::get('/orders', [OrderController::class, 'index']);
    Route::post('/orders', [OrderController::class, 'store']);
    Route::get('/orders/{order}', [OrderController::class, 'show']);
    Route::patch('/orders/{order}/status', [OrderController::class, 'updateStatus']);
    Route::post('/products/{product}/restock', [ProductController::class, 'restock']);
    Route::get('/reports/sales', [MiscController::class, 'salesReport']);
});

// Admin only.
Route::delete('/products/{product}', [ProductController::class, 'destroy'])->middleware('workbench.token:admin');
