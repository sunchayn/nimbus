<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
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

        Route::post('/relay', NimbusRelayController::class)
            ->name('nimbus.api.relay');

        Route::post('/responses/shape', ResponseShapeController::class)
            ->name('nimbus.api.responses.shape');
    },
);
