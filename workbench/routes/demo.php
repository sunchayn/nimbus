<?php

use Illuminate\Support\Facades\Route;
use Workbench\App\Http\Controllers\Demo\AuthController;
use Workbench\App\Http\Controllers\Demo\ContentController;
use Workbench\App\Http\Controllers\Demo\DemoController;
use Workbench\App\Http\Controllers\Demo\DumpAndDieController;
use Workbench\App\Http\Controllers\Demo\EchoController;
use Workbench\App\Http\Controllers\Demo\RouteMetadataController;
use Workbench\App\Http\Controllers\Demo\ShapesController;
use Workbench\App\Http\Controllers\Demo\UploadsController;
use Workbench\App\Http\Controllers\Demo\VerbsController;

Route::prefix('inline-validation')->group(function () {
    Route::post('/simple', [DemoController::class, 'simpleValidation'])
        ->name('demo.inline-validation.simple');
    Route::post('/complex', [DemoController::class, 'complexValidation'])
        ->name('demo.inline-validation.complex');
    Route::post('/conditional', [DemoController::class, 'conditionalValidation'])
        ->name('demo.inline-validation.conditional');
    Route::post('/enum', [DemoController::class, 'enumValidation'])
        ->name('demo.inline-validation.enum');
    Route::post('/validator-make', [DemoController::class, 'validatorMakeValidation'])
        ->name('demo.inline-validation.validator-make');
});

Route::prefix('shapes')->group(function () {
    Route::post('/nested-object', [\Workbench\App\Http\Controllers\Demo\ShapesController::class, 'nestedObject'])
        ->name('demo.shapes.nested-object');

    Route::post('/array-of-primitives', [\Workbench\App\Http\Controllers\Demo\ShapesController::class, 'arrayOfPrimitives'])
        ->name('demo.shapes.array-of-primitives');

    Route::post('/rule-coverage', [\Workbench\App\Http\Controllers\Demo\ShapesController::class, 'ruleCoverage'])
        ->name('demo.shapes.rule-coverage');

    Route::post('/rule-objects', [\Workbench\App\Http\Controllers\Demo\ShapesController::class, 'ruleObjects'])
        ->name('demo.shapes.rule-objects');
});

Route::get('/query-parameters', [\Workbench\App\Http\Controllers\Demo\QueryParametersController::class, 'index'])
    ->name('demo.query-parameters.index');

Route::prefix('responses')->group(function () {
    Route::get('/error-responses', [DemoController::class, 'errorResponses'])
        ->name('demo.responses.error-responses');

    Route::get('/success-responses', [DemoController::class, 'successResponses'])
        ->name('demo.responses.success-responses');

    Route::get('/json-resource', [\Workbench\App\Http\Controllers\Demo\ResponseDemoController::class, 'jsonResource'])
        ->name('demo.responses.json-resource');
    Route::get('/nested-json', [\Workbench\App\Http\Controllers\Demo\ResponseDemoController::class, 'nestedJson'])
        ->name('demo.responses.nested-json');
    Route::get('/spatie-data', [\Workbench\App\Http\Controllers\Demo\ResponseDemoController::class, 'spatieData'])
        ->name('demo.responses.spatie-data');
    Route::get('/inline-json', [\Workbench\App\Http\Controllers\Demo\ResponseDemoController::class, 'inlineJson'])
        ->name('demo.responses.inline-json');
    Route::get('/paginated', [\Workbench\App\Http\Controllers\Demo\ResponseDemoController::class, 'paginated'])
        ->name('demo.responses.paginated');
    Route::get('/simple-paginated', [\Workbench\App\Http\Controllers\Demo\ResponseDemoController::class, 'simplePaginated'])
        ->name('demo.responses.simple-paginated');
    Route::get('/cursor-paginated', [\Workbench\App\Http\Controllers\Demo\ResponseDemoController::class, 'cursorPaginated'])
        ->name('demo.responses.cursor-paginated');
    Route::get('/optional-keys', [\Workbench\App\Http\Controllers\Demo\ResponseDemoController::class, 'resourceWithOptionalKeys'])
        ->name('demo.responses.optional-keys');
    Route::get('/raw-payload', [\Workbench\App\Http\Controllers\Demo\ResponseDemoController::class, 'rawPayload'])
        ->name('demo.responses.raw-payload');
});

Route::prefix('spatie-data')->group(function () {
    Route::get('/', [DemoController::class, 'spatieData'])
        ->name('demo.spatie-data.index');
});

Route::prefix('authentication')->group(function () {
    Route::get('/show-logged-in-user', fn (\Illuminate\Http\Request $request) => response()->json([
        'user_id' => $request->user()->id ?? '<not logged in>',
        'user_agent' => $request->userAgent(),
        'login_url' => config('app.url').'/login',
    ])->withCookie('secret', 'value'))
        ->name('demo.authentication.show-logged-in-user');
});

Route::prefix('verbs')->group(function () {
    Route::get('/verbs', [VerbsController::class, 'get'])
        ->name('demo.verbs.get');
    Route::post('/verbs', [VerbsController::class, 'post'])
        ->name('demo.verbs.post');
    Route::patch('/verbs', [VerbsController::class, 'patch'])
        ->name('demo.verbs.patch');
});

Route::prefix('segments')->group(function () {
    Route::get('/{segment}', [VerbsController::class, 'get'])
        ->name('demo.segments.segment');

    Route::prefix('workspaces')->group(function () {
        Route::get('/members/list', [VerbsController::class, 'get'])
            ->name('demo.segments.workspaces.members.list');
        Route::post('/members/invite', [VerbsController::class, 'post'])
            ->name('demo.segments.workspaces.members.invite');
        Route::get('/projects/list', [VerbsController::class, 'get'])
            ->name('demo.segments.workspaces.projects.list');
        Route::post('/projects/create', [VerbsController::class, 'post'])
            ->name('demo.segments.workspaces.projects.create');
        Route::patch('/{workspace}/projects/{project}/archive', [VerbsController::class, 'patch'])
            ->name('demo.segments.workspaces.workspace.projects.project.archive');
    });

    Route::prefix('billing')->group(function () {
        Route::get('/invoices/list', [VerbsController::class, 'get'])
            ->name('demo.segments.billing.invoices.list');
        Route::post('/invoices/{invoice}/payments/refund', [VerbsController::class, 'post'])
            ->name('demo.segments.billing.invoices.invoice.payments.refund');
        Route::get('/plans/list', [VerbsController::class, 'get'])
            ->name('demo.segments.billing.plans.list');
    });
});

Route::prefix('dd')->group(function () {
    Route::get('/', DumpAndDieController::class)
        ->name('demo.dd.index');
});

Route::prefix('request-parameters')->group(function () {
    Route::get('/', [DemoController::class, 'requestParameters'])
        ->name('demo.request-parameters.get');
    Route::post('/', [DemoController::class, 'requestParameters'])
        ->name('demo.request-parameters.post');
});

Route::prefix('content')->group(function () {
    Route::get('/text', [ContentController::class, 'text'])
        ->name('demo.content.text');
    Route::get('/html', [ContentController::class, 'html'])
        ->name('demo.content.html');
    Route::get('/xml', [ContentController::class, 'xml'])
        ->name('demo.content.xml');
    Route::get('/csv', [ContentController::class, 'csv'])
        ->name('demo.content.csv');
    Route::get('/download', [ContentController::class, 'download'])
        ->name('demo.content.download');
    Route::get('/no-content', [ContentController::class, 'noContent'])
        ->name('demo.content.no-content');
    Route::get('/redirect', [ContentController::class, 'redirect'])
        ->name('demo.content.redirect');
    Route::get('/empty', [ContentController::class, 'empty'])
        ->name('demo.content.empty');
    Route::get('/large', [ContentController::class, 'large'])
        ->name('demo.content.large');
    Route::get('/slow', [ContentController::class, 'slow'])
        ->name('demo.content.slow');
    Route::get('/invalid-json', [ContentController::class, 'invalidJson'])
        ->name('demo.content.invalid-json');
});

Route::get('/status/{code}', [ContentController::class, 'status'])->whereNumber('code')
    ->name('demo.status.code');

Route::prefix('echo')->group(function () {
    Route::match(['get', 'post', 'put', 'patch', 'delete'], '/', [EchoController::class, 'echo'])
        ->name('demo.echo.index');
    Route::post('/plain-text', [EchoController::class, 'text'])
        ->name('demo.echo.plain-text');
    Route::get('/redirect', [EchoController::class, 'redirect'])
        ->name('demo.echo.redirect');
    Route::get('/slow', [EchoController::class, 'slow'])
        ->name('demo.echo.slow');
});

Route::prefix('uploads')->group(function () {
    Route::post('/multipart/single', [UploadsController::class, 'multipartSingle'])
        ->name('demo.uploads.multipart.single');
    Route::post('/multipart/multiple', [UploadsController::class, 'multipartMultiple'])
        ->name('demo.uploads.multipart.multiple');
    Route::post('/multipart/mixed', [UploadsController::class, 'multipartMixed'])
        ->name('demo.uploads.multipart.mixed');
    Route::post('/json/single', [UploadsController::class, 'jsonSingle'])
        ->name('demo.uploads.json.single');
    Route::post('/json/multiple', [UploadsController::class, 'jsonMultiple'])
        ->name('demo.uploads.json.multiple');
});

Route::post('/login-form', [EchoController::class, 'form'])
    ->name('demo.login-form');

Route::prefix('auth')->group(function () {
    Route::get('/bearer', [AuthController::class, 'bearer'])
        ->name('demo.auth.bearer');
    Route::get('/admin', [AuthController::class, 'admin'])
        ->name('demo.auth.admin');
    Route::get('/basic', [AuthController::class, 'basic'])
        ->name('demo.auth.basic');
    Route::get('/session', [AuthController::class, 'session'])
        ->name('demo.auth.session');
});

Route::get('/route-metadata/products/{product}', [RouteMetadataController::class, 'show'])
    ->middleware(['auth:web', 'throttle:30,1'])
    ->name('demo.route-metadata.products.show');

Route::post('/shapes/unresolvable-rules', [ShapesController::class, 'unresolvableRules'])
    ->name('demo.shapes.unresolvable-rules');

Route::prefix('v2')->group(function () {
    Route::get('/verbs', [VerbsController::class, 'get'])
        ->name('demo.v2.verbs.get');
    Route::post('/verbs', [VerbsController::class, 'post'])
        ->name('demo.v2.verbs.post');
    Route::get('/echo', [EchoController::class, 'echo'])
        ->name('demo.v2.echo');
});

Route::get('/signed/unsubscribe/{user}', [EchoController::class, 'echo'])
    ->middleware('signed')
    ->name('demo.signed.unsubscribe');
