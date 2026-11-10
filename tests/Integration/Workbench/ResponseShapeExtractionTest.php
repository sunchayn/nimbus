<?php

namespace Sunchayn\Nimbus\Tests\Integration\Workbench;

use Illuminate\Routing\Router;
use PHPUnit\Framework\Attributes\CoversClass;
use Sunchayn\Nimbus\Http\Api\Responses\ResponseShapeController;
use Sunchayn\Nimbus\Modules\Extractor\Actions\InferSchemaFromResourceAstAction;
use Sunchayn\Nimbus\Modules\Extractor\Services\Response\Strategies\PaginatedResourceCollectionStrategy;
use Sunchayn\Nimbus\Modules\Schemas\ValueObjects\OptionalSchemaProperty;
use Sunchayn\Nimbus\Tests\TestCase;

#[CoversClass(ResponseShapeController::class)]
#[CoversClass(PaginatedResourceCollectionStrategy::class)]
#[CoversClass(InferSchemaFromResourceAstAction::class)]
#[CoversClass(OptionalSchemaProperty::class)]
class ResponseShapeExtractionTest extends TestCase
{
    protected function defineRoutes($router): void
    {
        /** @var Router $router */
        $router->prefix('demo')->group(__DIR__.'/../../../workbench/routes/demo.php');
    }

    public function test_it_wraps_a_paginated_collection_in_the_data_links_and_meta_envelope(): void
    {
        // Act

        $shape = $this->requestShape('GET', 'demo/responses/paginated')['shape'];

        // Assert

        $this->assertSame(['data', 'links', 'meta'], array_keys($shape['properties']));
        $this->assertSame('array', $shape['properties']['data']['type']);
        $this->assertArrayHasKey('title', $shape['properties']['data']['items']['properties']);
        $this->assertSame(
            ['current_page', 'from', 'last_page', 'links', 'path', 'per_page', 'to', 'total'],
            array_keys($shape['properties']['meta']['properties']),
        );
        $this->assertSame(['first', 'last', 'prev', 'next'], array_keys($shape['properties']['links']['properties']));
    }

    public function test_it_keeps_the_resource_shape_when_nothing_is_paginated(): void
    {
        // Act

        $shape = $this->requestShape('GET', 'demo/responses/json-resource')['shape'];

        // Assert

        $this->assertSame(['id', 'name', 'email', 'is_active'], array_keys($shape['properties']));
    }

    public function test_it_uses_the_simple_paginator_meta(): void
    {
        // Act

        $shape = $this->requestShape('GET', 'demo/responses/simple-paginated')['shape'];

        // Assert

        $this->assertSame(
            ['current_page', 'from', 'path', 'per_page', 'to'],
            array_keys($shape['properties']['meta']['properties']),
        );
    }

    public function test_it_uses_the_cursor_paginator_meta_through_a_variable(): void
    {
        // Act

        $shape = $this->requestShape('GET', 'demo/responses/cursor-paginated')['shape'];

        // Assert

        $this->assertSame(
            ['path', 'per_page', 'next_cursor', 'prev_cursor'],
            array_keys($shape['properties']['meta']['properties']),
        );
    }

    public function test_it_marks_conditional_resource_keys_as_optional(): void
    {
        // Act

        $shape = $this->requestShape('GET', 'demo/responses/optional-keys')['shape'];

        // Assert

        $this->assertSame(['id', 'title'], $shape['required']);
        $this->assertSame(['id', 'title', 'internal_note', 'views', 'category', 'body'], array_keys($shape['properties']));
        $this->assertSame('integer', $shape['properties']['views']['type']);
    }

    /**
     * @return array<string, mixed>
     */
    private function requestShape(string $method, string $uri): array
    {
        return $this
            ->postJson(route('nimbus.api.responses.shape'), ['method' => $method, 'endpoint' => '/'.$uri])
            ->assertOk()
            ->json();
    }
}
