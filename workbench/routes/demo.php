<?php

use Illuminate\Support\Facades\Route;
use Workbench\App\Http\Controllers\Demo\AuthController;
use Workbench\App\Http\Controllers\Demo\ContentController;
use Workbench\App\Http\Controllers\Demo\DemoController;
use Workbench\App\Http\Controllers\Demo\DumpAndDieController;
use Workbench\App\Http\Controllers\Demo\EchoController;
use Workbench\App\Http\Controllers\Demo\ShapesController;
use Workbench\App\Http\Controllers\Demo\VerbsController;

Route::prefix('inline-validation')->group(function () {
    Route::post('/simple', [DemoController::class, 'simpleValidation']);
    Route::post('/complex', [DemoController::class, 'complexValidation']);
    Route::post('/conditional', [DemoController::class, 'conditionalValidation']);
    Route::post('/enum', [DemoController::class, 'enumValidation']);
    Route::post('/validator-make', [DemoController::class, 'validatorMakeValidation']);
});

Route::prefix('shapes')->group(function () {
    Route::post('/nested-object', [\Workbench\App\Http\Controllers\Demo\ShapesController::class, 'nestedObject']);

    Route::post('/array-of-primitives', [\Workbench\App\Http\Controllers\Demo\ShapesController::class, 'arrayOfPrimitives']);
});

Route::prefix('responses')->group(function () {
    Route::get('/error-responses', [DemoController::class, 'errorResponses']);

    Route::get('/success-responses', [DemoController::class, 'successResponses']);

    Route::get('/json-resource', [\Workbench\App\Http\Controllers\Demo\ResponseDemoController::class, 'jsonResource']);
    Route::get('/nested-json', [\Workbench\App\Http\Controllers\Demo\ResponseDemoController::class, 'nestedJson']);
    Route::get('/spatie-data', [\Workbench\App\Http\Controllers\Demo\ResponseDemoController::class, 'spatieData']);
    Route::get('/inline-json', [\Workbench\App\Http\Controllers\Demo\ResponseDemoController::class, 'inlineJson']);
    Route::get('/raw-payload', [\Workbench\App\Http\Controllers\Demo\ResponseDemoController::class, 'rawPayload'])->name('raw-payload');
});

Route::prefix('spatie-data')->group(function () {
    Route::get('/', [DemoController::class, 'spatieData']);
});

Route::prefix('authentication')->group(function () {
    Route::get('/show-logged-in-user', fn (\Illuminate\Http\Request $request) => response()->json([
        'user_id' => $request->user()->id ?? '<not logged in>',
        'user_agent' => $request->userAgent(),
        'login_url' => config('app.url').'/login',
    ])->withCookie('secret', 'value'));
});

Route::prefix('verbs')->group(function () {
    Route::get('/verbs', [VerbsController::class, 'get']);
    Route::post('/verbs', [VerbsController::class, 'post']);
    Route::patch('/verbs', [VerbsController::class, 'patch']);
});

Route::prefix('segments')->group(function () {
    Route::get('/{segment}', [VerbsController::class, 'get']);
    Route::post('/example2/{segment}/create', [VerbsController::class, 'post']);
});

Route::prefix('dd')->group(function () {
    Route::get('/', DumpAndDieController::class);
});

Route::prefix('request-parameters')->group(function () {
    Route::get('/', [DemoController::class, 'requestParameters']);
    Route::post('/', [DemoController::class, 'requestParameters']);
});

Route::prefix('content')->group(function () {
    Route::get('/text', [ContentController::class, 'text']);
    Route::get('/html', [ContentController::class, 'html']);
    Route::get('/xml', [ContentController::class, 'xml']);
    Route::get('/csv', [ContentController::class, 'csv']);
    Route::get('/download', [ContentController::class, 'download']);
    Route::get('/no-content', [ContentController::class, 'noContent']);
    Route::get('/redirect', [ContentController::class, 'redirect']);
    Route::get('/empty', [ContentController::class, 'empty']);
    Route::get('/large', [ContentController::class, 'large']);
    Route::get('/slow', [ContentController::class, 'slow']);
    Route::get('/invalid-json', [ContentController::class, 'invalidJson']);
});

Route::get('/status/{code}', [ContentController::class, 'status'])->whereNumber('code');

Route::prefix('echo')->group(function () {
    Route::match(['get', 'post', 'put', 'patch', 'delete'], '/', [EchoController::class, 'echo']);
    Route::post('/plain-text', [EchoController::class, 'text']);
});

Route::post('/uploads', [EchoController::class, 'upload']);
Route::post('/login-form', [EchoController::class, 'form']);

Route::prefix('auth')->group(function () {
    Route::get('/bearer', [AuthController::class, 'bearer']);
    Route::get('/admin', [AuthController::class, 'admin']);
    Route::get('/basic', [AuthController::class, 'basic']);
    Route::get('/session', [AuthController::class, 'session']);
});

Route::post('/shapes/unresolvable-rules', [ShapesController::class, 'unresolvableRules'])->name('unresolvable-rules');

Route::prefix('v2')->group(function () {
    Route::get('/verbs', [VerbsController::class, 'get']);
    Route::post('/verbs', [VerbsController::class, 'post']);
    Route::get('/echo', [EchoController::class, 'echo']);
});
