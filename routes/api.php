<?php

use Illuminate\Support\Facades\Route;
use Sunchayn\Nimbus\Http\Api\Collections\NimbusCollectionsController;
use Sunchayn\Nimbus\Http\Api\Relay\NimbusRelayController;

Route::group(
    [
        'domain' => config('nimbus.domain'),
        'prefix' => config('nimbus.prefix').'/api',
    ],
    function () {
        Route::post('/relay', NimbusRelayController::class)
            ->name('nimbus.api.relay');

        Route::get('/collections', [NimbusCollectionsController::class, 'index'])
            ->name('nimbus.api.collections.index');

        Route::put('/collections', [NimbusCollectionsController::class, 'sync'])
            ->name('nimbus.api.collections.sync');
    },
);
