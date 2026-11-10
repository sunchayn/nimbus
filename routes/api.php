<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use Sunchayn\Nimbus\Http\Api\Actors\ListActorsController;
use Sunchayn\Nimbus\Http\Api\Doctor\DoctorController;
use Sunchayn\Nimbus\Http\Api\Magic\LookupValuesController;
use Sunchayn\Nimbus\Http\Api\Magic\SignedUrlController;
use Sunchayn\Nimbus\Http\Api\Relay\NimbusRelayController;
use Sunchayn\Nimbus\Http\Api\Responses\ResponseShapeController;
use Sunchayn\Nimbus\Http\Api\Routes\RoutesController;
use Sunchayn\Nimbus\Http\Api\Toolbox\ToolboxController;

Route::group(
    [
        'domain' => config('nimbus.domain'),
        'prefix' => config('nimbus.prefix').'/api',
    ],
    function () {
        // The web group decrypts the active application cookie, as the index page does.
        Route::get('/routes', RoutesController::class)
            ->middleware('web')
            ->name('nimbus.api.routes');

        Route::get('/actors', ListActorsController::class)
            ->middleware('web')
            ->name('nimbus.api.actors');

        Route::get('/doctor', DoctorController::class)
            ->middleware('web')
            ->name('nimbus.api.doctor');

        Route::post('/relay', NimbusRelayController::class)
            ->name('nimbus.api.relay');

        Route::post('/magic/signed-url', SignedUrlController::class)
            ->name('nimbus.api.magic.signed-url');

        Route::post('/magic/lookup-values', LookupValuesController::class)
            ->name('nimbus.api.magic.lookup-values');

        Route::post('/responses/shape', ResponseShapeController::class)
            ->name('nimbus.api.responses.shape');

        Route::prefix('toolbox')->name('nimbus.api.toolbox.')->group(function () {
            Route::post('/decrypt', [ToolboxController::class, 'decrypt'])->name('decrypt');
            Route::post('/encrypt', [ToolboxController::class, 'encrypt'])->name('encrypt');
            Route::post('/signed-url/verify', [ToolboxController::class, 'verifySignedUrl'])->name('signed-url.verify');
            Route::post('/signed-url/create', [ToolboxController::class, 'createSignedUrl'])->name('signed-url.create');
            Route::post('/hash', [ToolboxController::class, 'hash'])->name('hash');
            Route::post('/token', [ToolboxController::class, 'token'])->name('token');
            Route::post('/session', [ToolboxController::class, 'session'])->name('session');
        });
    },
);
