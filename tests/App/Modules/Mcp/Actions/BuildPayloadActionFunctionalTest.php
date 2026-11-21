<?php

declare(strict_types=1);

namespace Sunchayn\Nimbus\Tests\App\Modules\Mcp\Actions;

use PHPUnit\Framework\Attributes\CoversClass;
use Sunchayn\Nimbus\Modules\Magic\Actions\ReadLookupValuesAction;
use Sunchayn\Nimbus\Modules\Mcp\Actions\BuildPayloadAction;
use Sunchayn\Nimbus\Modules\Mcp\Exceptions\McpToolException;
use Sunchayn\Nimbus\Modules\Routes\Actions\ExtractRoutesAction;
use Sunchayn\Nimbus\Modules\Routes\Collections\ExtractedRoutesCollection;
use Sunchayn\Nimbus\Modules\Routes\DataTransferObjects\ExtractedRoute;
use Sunchayn\Nimbus\Modules\Routes\Exceptions\RouteNotFoundException;
use Sunchayn\Nimbus\Modules\Routes\ValueObjects\Endpoint;
use Sunchayn\Nimbus\Modules\Schemas\ValueObjects\IntegerSchemaProperty;
use Sunchayn\Nimbus\Modules\Schemas\ValueObjects\PropertyAnnotations;
use Sunchayn\Nimbus\Modules\Schemas\ValueObjects\Schema;
use Sunchayn\Nimbus\Tests\App\Modules\Mcp\Concerns\FakesExtractedRoutes;
use Sunchayn\Nimbus\Tests\TestCase;

#[CoversClass(BuildPayloadAction::class)]
class BuildPayloadActionFunctionalTest extends TestCase
{
    use FakesExtractedRoutes;

    public function test_it_builds_a_payload_from_overrides(): void
    {
        // Arrange

        $this->fakeExtractedRoutes();

        $action = resolve(BuildPayloadAction::class);

        // Act

        $built = $action->execute(
            route: 'POST api/users',
            mode: 'valid',
            overrides: ['name' => 'Jane Doe'],
        );

        // Assert

        $this->assertSame(
            'POST api/users',
            $built['route'],
        );

        $this->assertSame(
            'Jane Doe',
            $built['payload']['name'],
        );

        $this->assertArrayHasKey(
            'email',
            $built['payload'],
        );
    }

    public function test_it_rejects_an_unknown_mode(): void
    {
        // Arrange

        $this->fakeExtractedRoutes();

        $action = resolve(BuildPayloadAction::class);

        // Assert

        $this->expectException(McpToolException::class);

        // Act

        $action->execute(
            route: 'POST api/users',
            mode: 'wild',
        );
    }

    public function test_it_rejects_an_unknown_route(): void
    {
        // Arrange

        $this->fakeExtractedRoutes();

        $action = resolve(BuildPayloadAction::class);

        // Assert

        $this->expectException(RouteNotFoundException::class);

        // Act

        $action->execute(route: 'GET nowhere');
    }

    public function test_it_fills_an_exists_rule_with_an_existing_value(): void
    {
        // Arrange

        $route = new ExtractedRoute(
            new Endpoint('v1', 'posts', '/api/posts'),
            ['POST'],
            new Schema([
                new IntegerSchemaProperty(
                    name: 'author_id',
                    required: true,
                    propertyAnnotations: new PropertyAnnotations(lookup: ['table' => 'users', 'column' => 'id']),
                ),
            ]),
        );

        config()->set('nimbus.applications', ['main' => config('nimbus.applications.main')]);

        $extractRoutesActionMock = $this->mock(ExtractRoutesAction::class);

        $extractRoutesActionMock
            ->shouldReceive('execute')
            ->andReturn(new ExtractedRoutesCollection([$route]));

        $lookupValuesMock = $this->mock(ReadLookupValuesAction::class);

        // Anticipate

        $lookupValuesMock
            ->shouldReceive('execute')
            ->andReturn(['users.id' => [42]]);

        $action = resolve(BuildPayloadAction::class);

        // Act

        $built = $action->execute(route: 'POST api/posts');

        // Assert

        $this->assertSame(
            42,
            $built['payload']['author_id'],
        );

        $lookupValuesMock
            ->shouldHaveReceived('execute')
            ->once()
            ->with(
                [['table' => 'users', 'column' => 'id']],
                [['table' => 'users', 'column' => 'id']],
            );
    }
}
