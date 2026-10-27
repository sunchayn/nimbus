<?php

declare(strict_types=1);

use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Route;
use Workbench\App\Http\Controllers\Dev\DevAuthController;

Route::get('/', function (): RedirectResponse {
    return redirect('/demo');
});

Route::prefix('dev')->group(function () {
    Route::get('/login-as/{id}', [DevAuthController::class, 'loginAs']);
    Route::get('/login-as-new-user', [DevAuthController::class, 'loginAsNewUser']);
});
