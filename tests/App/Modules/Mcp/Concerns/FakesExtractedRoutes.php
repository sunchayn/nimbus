<?php

declare(strict_types=1);

namespace Sunchayn\Nimbus\Tests\App\Modules\Mcp\Concerns;

use Sunchayn\Nimbus\Modules\Routes\Actions\ExtractRoutesAction;
use Sunchayn\Nimbus\Modules\Routes\Collections\ExtractedRoutesCollection;
use Sunchayn\Nimbus\Modules\Routes\DataTransferObjects\ExtractedRoute;
use Sunchayn\Nimbus\Modules\Routes\ValueObjects\Endpoint;
use Sunchayn\Nimbus\Modules\Schemas\Enums\StringFormat;
use Sunchayn\Nimbus\Modules\Schemas\ValueObjects\IntegerSchemaProperty;
use Sunchayn\Nimbus\Modules\Schemas\ValueObjects\Schema;
use Sunchayn\Nimbus\Modules\Schemas\ValueObjects\StringSchemaProperty;

trait FakesExtractedRoutes
{
    /**
     * Binds an extraction with a users store route, a users index route, and a route with two methods.
     */
    protected function fakeExtractedRoutes(): void
    {
        $storeSchema = new Schema([
            new StringSchemaProperty(
                name: 'email',
                required: true,
                stringFormat: StringFormat::EMAIL,
            ),
            new StringSchemaProperty(
                name: 'name',
                required: true,
                minLength: 3,
            ),
            new IntegerSchemaProperty(
                name: 'age',
                minimum: 18,
            ),
        ]);

        $storeRoute = new ExtractedRoute(
            new Endpoint('v1', 'users', '/api/users'),
            ['POST'],
            $storeSchema,
            ['name' => 'users.store'],
            ['api/users', 'users.store'],
        );

        $indexRoute = new ExtractedRoute(
            new Endpoint('v1', 'users', '/api/users'),
            ['GET', 'HEAD'],
            Schema::empty(),
            ['name' => 'users.index'],
            ['api/users', 'users.index'],
        );

        $updateRoute = new ExtractedRoute(
            new Endpoint('v1', 'posts', '/api/posts/{post}'),
            ['PUT', 'PATCH'],
            Schema::empty(),
            [],
            ['api/posts/{post}'],
        );

        $routes = new ExtractedRoutesCollection([$storeRoute, $indexRoute, $updateRoute]);

        config()->set('nimbus.applications', ['main' => config('nimbus.applications.main')]);

        $extractRoutesActionMock = $this->mock(ExtractRoutesAction::class);

        $extractRoutesActionMock
            ->shouldReceive('execute')
            ->andReturn($routes);
    }
}
