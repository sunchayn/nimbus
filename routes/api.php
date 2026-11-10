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
    },
);
