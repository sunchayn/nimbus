<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use Sunchayn\Nimbus\Http\Api\Actors\Controllers\ListActorsController;
use Sunchayn\Nimbus\Http\Api\Doctor\Controllers\DoctorController;
use Sunchayn\Nimbus\Http\Api\Magic\Controllers\LookupValuesController;
use Sunchayn\Nimbus\Http\Api\Magic\Controllers\SignedUrlController;
use Sunchayn\Nimbus\Http\Api\Relay\Controllers\NimbusRelayController;
use Sunchayn\Nimbus\Http\Api\Responses\Controllers\ResponseShapeController;
use Sunchayn\Nimbus\Http\Api\Routes\Controllers\RoutesController;
use Sunchayn\Nimbus\Http\Api\Toolbox\Controllers\ToolboxController;
use Sunchayn\Nimbus\Http\Api\Workspace\Controllers\CreateWorkspaceFolderController;
use Sunchayn\Nimbus\Http\Api\Workspace\Controllers\DeleteWorkspaceFolderController;
use Sunchayn\Nimbus\Http\Api\Workspace\Controllers\DeleteWorkspaceRequestController;
use Sunchayn\Nimbus\Http\Api\Workspace\Controllers\ListWorkspaceController;
use Sunchayn\Nimbus\Http\Api\Workspace\Controllers\MoveWorkspaceRequestController;
use Sunchayn\Nimbus\Http\Api\Workspace\Controllers\RenameWorkspaceFolderController;
use Sunchayn\Nimbus\Http\Api\Workspace\Controllers\ReorderWorkspaceFoldersController;
use Sunchayn\Nimbus\Http\Api\Workspace\Controllers\ReorderWorkspaceRequestsController;
use Sunchayn\Nimbus\Http\Api\Workspace\Controllers\SaveWorkspaceRequestController;
use Sunchayn\Nimbus\Http\Api\Workspace\Controllers\ShowWorkspaceRequestController;

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

        Route::prefix('/workspace')
            ->where(['id' => '[A-Za-z0-9_-]{8,64}'])
            ->group(function () {
                Route::get('/', ListWorkspaceController::class)
                    ->name('nimbus.api.workspace.index');

                Route::get('/requests/{id}', ShowWorkspaceRequestController::class)
                    ->name('nimbus.api.workspace.requests.show');

                Route::put('/requests/{id}', SaveWorkspaceRequestController::class)
                    ->name('nimbus.api.workspace.requests.save');

                Route::delete('/requests/{id}', DeleteWorkspaceRequestController::class)
                    ->name('nimbus.api.workspace.requests.delete');

                Route::patch('/requests/{id}/move', MoveWorkspaceRequestController::class)
                    ->name('nimbus.api.workspace.requests.move');

                Route::post('/folders', CreateWorkspaceFolderController::class)
                    ->name('nimbus.api.workspace.folders.create');

                Route::patch('/folders', RenameWorkspaceFolderController::class)
                    ->name('nimbus.api.workspace.folders.rename');

                Route::delete('/folders', DeleteWorkspaceFolderController::class)
                    ->name('nimbus.api.workspace.folders.delete');

                Route::put('/folders/order', ReorderWorkspaceFoldersController::class)
                    ->name('nimbus.api.workspace.folders.reorder');

                Route::put('/requests/order', ReorderWorkspaceRequestsController::class)
                    ->name('nimbus.api.workspace.requests.reorder');
            });
    },
);
