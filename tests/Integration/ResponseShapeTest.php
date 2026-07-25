<?php

declare(strict_types=1);

namespace Sunchayn\Nimbus\Tests\Integration;

use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Route;
use PHPUnit\Framework\Attributes\CoversClass;
use Sunchayn\Nimbus\Http\Api\Responses\ResponseShapeController;
use Sunchayn\Nimbus\Http\Api\Responses\ResponseShapeRequest;
use Sunchayn\Nimbus\Http\Api\Responses\ResponseShapeResource;
use Sunchayn\Nimbus\Modules\Extractor\Services\Response\ResponseSchemaExtractor;
use Sunchayn\Nimbus\Modules\Schemas\ValueObjects\Schema;
use Sunchayn\Nimbus\Modules\Schemas\ValueObjects\StringSchemaProperty;
use Sunchayn\Nimbus\Tests\TestCase;

#[CoversClass(ResponseShapeRequest::class)]
#[CoversClass(ResponseShapeController::class)]
#[CoversClass(ResponseShapeResource::class)]
class ResponseShapeTest extends TestCase
{
    public function test_it_returns_shape_resolved_by_extractor(): void
    {
        // Arrange

        $extractorMock = $this->mock(ResponseSchemaExtractor::class);

        $schema = new Schema([
            new StringSchemaProperty(name: 'mocked_key'),
        ]);

        // Anticipate

        $extractorMock
            ->shouldReceive('extract')
            ->once()
            ->withAnyArgs()
            ->andReturn($schema);

        // Act

        $response = $this->postJson(route('nimbus.api.responses.shape'), [
            'method' => 'GET',
            'endpoint' => '/test-mock-route',
            'response_body' => '{"id":1}',
        ]);

        // Assert

        $response->assertStatus(200);

        $response->assertJson([
            'shape' => [
                '$schema' => 'https://json-schema.org/draft/2020-12/schema',
                'type' => 'object',
                'properties' => [
                    'mocked_key' => [
                        'type' => 'string',
                    ],
                ],
                'required' => [],
                'additionalProperties' => false,
            ],
        ]);
    }

    public function test_it_fails_validation_when_parameters_are_missing(): void
    {
        // Act

        $response = $this->postJson(route('nimbus.api.responses.shape'), []);

        // Assert

        $response->assertStatus(422);

        $response->assertJsonValidationErrors(['method', 'endpoint']);
    }

    public function test_it_allows_nullable_response_body(): void
    {
        // Arrange

        $extractorMock = $this->mock(ResponseSchemaExtractor::class);

        $schema = new Schema([]);

        // Anticipate

        $extractorMock
            ->shouldReceive('extract')
            ->once()
            ->withAnyArgs()
            ->andReturn($schema);

        // Act

        $response = $this->postJson(route('nimbus.api.responses.shape'), [
            'method' => 'GET',
            'endpoint' => '/test-nullable-route',
            'response_body' => null,
        ]);

        // Assert

        $response->assertStatus(200);
    }

    public function test_it_integrates(): void
    {
        // Arrange

        Route::get('/test-integrate-route', [TestController::class, 'showResource']);

        // Act

        $response = $this->postJson(route('nimbus.api.responses.shape'), [
            'method' => 'GET',
            'endpoint' => '/test-integrate-route',
            'response_body' => json_encode([
                'id' => 123,
                'name' => 'John Doe',
                'email' => 'john@example.com',
            ]),
        ]);

        // Assert

        $response->assertStatus(200);

        $response->assertJsonStructure([
            'shape' => [
                '$schema',
                'type',
                'properties',
                'required',
                'additionalProperties',
            ],
        ]);
    }
}

class TestUserResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'id' => 123,
            'name' => 'John Doe',
            'email' => 'john@example.com',
        ];
    }
}

class TestController
{
    public function showResource()
    {
        return new TestUserResource(null);
    }
}
