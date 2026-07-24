<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use Sunchayn\Nimbus\Http\Api\Relay\NimbusRelayController;
use Sunchayn\Nimbus\Http\Api\Responses\ResponseShapeController;

Route::group(
    [
        'domain' => config('nimbus.domain'),
        'prefix' => config('nimbus.prefix').'/api',
    ],
    function () {
        Route::post('/relay', NimbusRelayController::class)
            ->name('nimbus.api.relay');

        Route::post('/responses/shape', ResponseShapeController::class)
            ->name('nimbus.api.responses.shape');
    },
);
