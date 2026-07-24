<?php

declare(strict_types=1);

namespace Sunchayn\Nimbus\Tests\App\Modules\Extractor\Actions;

use Illuminate\Http\Resources\Json\JsonResource;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Sunchayn\Nimbus\Modules\Ast\Actions\GetPhpTypeFromAstScalarAction;
use Sunchayn\Nimbus\Modules\Extractor\Actions\InferEloquentModelFromJsonResourceAction;
use Sunchayn\Nimbus\Modules\Extractor\Actions\InferSchemaFromResourceAstAction;
use Sunchayn\Nimbus\Modules\Extractor\Actions\InferSchemaPropertyFromDatabaseColumnAction;
use Sunchayn\Nimbus\Modules\Schemas\ValueObjects\IntegerSchemaProperty;
use Sunchayn\Nimbus\Modules\Schemas\ValueObjects\StringSchemaProperty;

#[CoversClass(InferSchemaFromResourceAstAction::class)]
class InferSchemaFromResourceAstActionUnitTest extends TestCase
{
    public function test_it_transforms_resource_to_schema(): void
    {
        // Arrange

        $typeResolver = new GetPhpTypeFromAstScalarAction;

        $modelResolver = $this->createMock(InferEloquentModelFromJsonResourceAction::class);

        $columnResolver = $this->createMock(InferSchemaPropertyFromDatabaseColumnAction::class);

        $action = new InferSchemaFromResourceAstAction(
            getPhpTypeFromAstScalarAction: $typeResolver,
            inferEloquentModelFromJsonResourceAction: $modelResolver,
            inferSchemaPropertyFromDatabaseColumnAction: $columnResolver,
        );

        // Anticipate

        $modelResolver->method('execute')->willReturn('App\Models\DummyUser');

        $columnResolver->method('execute')
            ->willReturnCallback(function ($model, $key) {
                if ($key === 'age') {
                    return new IntegerSchemaProperty('age', required: true);
                }

                return null;
            });

        // Act

        $schema = $action->execute(DummyUserResource::class);

        // Assert

        $this->assertEquals(
            [
                '$schema' => 'https://json-schema.org/draft/2020-12/schema',
                'type' => 'object',
                'properties' => [
                    'name' => [
                        'type' => 'string',
                    ],
                    'address' => [
                        'type' => 'object',
                        'properties' => [
                            'city' => [
                                'type' => 'string',
                            ],
                        ],
                        'required' => [
                            'city',
                        ],
                        'additionalProperties' => false,
                    ],
                    'friends' => [
                        'type' => 'array',
                        'items' => [
                            'type' => 'object',
                            'properties' => [
                                'city' => [
                                    'type' => 'string',
                                ],
                            ],
                            'required' => [
                                'city',
                            ],
                            'additionalProperties' => false,
                        ],
                    ],
                    'profile' => [
                        'type' => 'object',
                        'properties' => [
                            'city' => [
                                'type' => 'string',
                            ],
                        ],
                        'required' => [
                            'city',
                        ],
                        'additionalProperties' => false,
                    ],
                    'age' => [
                        'type' => 'integer',
                    ],
                    'custom' => [
                        'type' => 'string',
                    ],
                ],
                'required' => [
                    'name',
                    'address',
                    'friends',
                    'profile',
                    'age',
                    'custom',
                ],
                'additionalProperties' => false,
            ],
            $schema->toArray(),
        );
    }

    public function test_it_returns_empty_on_circular_references(): void
    {
        // Arrange

        $columnResolver = $this->createMock(InferSchemaPropertyFromDatabaseColumnAction::class);

        $action = new InferSchemaFromResourceAstAction(
            getPhpTypeFromAstScalarAction: new GetPhpTypeFromAstScalarAction,
            inferEloquentModelFromJsonResourceAction: new InferEloquentModelFromJsonResourceAction,
            inferSchemaPropertyFromDatabaseColumnAction: $columnResolver,
        );

        // Act

        $schema = $action->execute(CircularResource::class);

        // Assert

        $this->assertFalse($schema->isEmpty());
        $this->assertCount(1, $schema->properties);
        $this->assertInstanceOf(StringSchemaProperty::class, $schema->properties[0]);
        $this->assertEquals('circular', $schema->properties[0]->getName());
    }

    public function test_it_returns_empty_when_to_array_expression_is_not_array(): void
    {
        // Arrange

        $typeResolver = new GetPhpTypeFromAstScalarAction;
        $modelResolver = new InferEloquentModelFromJsonResourceAction;
        $columnResolver = $this->createMock(InferSchemaPropertyFromDatabaseColumnAction::class);

        $action = new InferSchemaFromResourceAstAction($typeResolver, $modelResolver, $columnResolver);

        // Act

        $schema = $action->execute(NonArrayResource::class);

        // Assert

        $this->assertTrue($schema->isEmpty());
    }

    public function test_it_handles_this_resource_property_fetch_and_non_resource_instantiations(): void
    {
        // Arrange

        $typeResolver = new GetPhpTypeFromAstScalarAction;
        $modelResolver = $this->createMock(InferEloquentModelFromJsonResourceAction::class);
        $modelResolver->method('execute')->willReturn('App\Models\DummyUser');

        $columnResolver = $this->createMock(InferSchemaPropertyFromDatabaseColumnAction::class);
        $columnResolver->method('execute')->willReturn(new StringSchemaProperty('title', required: true));

        $action = new InferSchemaFromResourceAstAction($typeResolver, $modelResolver, $columnResolver);

        // Act

        $schema = $action->execute(ResourceWithPropertyAndNonResourceCalls::class);

        // Assert

        $this->assertEquals([
            '$schema' => 'https://json-schema.org/draft/2020-12/schema',
            'type' => 'object',
            'properties' => [
                'title' => [
                    'type' => 'string',
                ],
                'nonResourceObj' => [
                    'type' => 'string',
                ],
                'nonResourceStatic' => [
                    'type' => 'string',
                ],
            ],
            'required' => [
                'title',
                'nonResourceObj',
                'nonResourceStatic',
            ],
            'additionalProperties' => false,
        ], $schema->toArray());
    }

    public function test_it_returns_null_for_property_fetch_when_model_resolver_returns_null(): void
    {
        // Arrange

        $modelResolver = $this->createMock(InferEloquentModelFromJsonResourceAction::class);

        $columnResolver = $this->createMock(InferSchemaPropertyFromDatabaseColumnAction::class);

        $action = new InferSchemaFromResourceAstAction(
            getPhpTypeFromAstScalarAction: new GetPhpTypeFromAstScalarAction,
            inferEloquentModelFromJsonResourceAction: $modelResolver,
            inferSchemaPropertyFromDatabaseColumnAction: $columnResolver,
        );

        // Anticipate

        $modelResolver->method('execute')->willReturn('App\Models\DummyUser');

        // Act

        $schema = $action->execute(ResourceWithPropertyFetchOnly::class);

        // Assert

        $this->assertCount(1, $schema->properties);

        $this->assertInstanceOf(StringSchemaProperty::class, $schema->properties[0]);

        $this->assertEquals('age', $schema->properties[0]->getName());
    }
}

class ResourceWithPropertyAndNonResourceCalls extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'title' => $this->resource->title,
            'nonResourceObj' => new \stdClass,
            'nonResourceStatic' => \stdClass::collection([]),
        ];
    }
}

class ResourceWithPropertyFetchOnly extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'age' => $this->age,
        ];
    }
}

class DummyAddressResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'city' => 'Paris',
        ];
    }
}

class DummyUserResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'name' => 'John',
            'address' => new DummyAddressResource(null),
            'friends' => DummyAddressResource::collection([]),
            'profile' => DummyAddressResource::make(null),
            ...[1, 2, 3],
            123 => 'value',
            'age' => $this->age,
            'custom' => $this->someMethod(),
        ];
    }
}

class NonArrayResource extends JsonResource
{
    public function toArray($request): array
    {
        return parent::toArray($request);
    }
}

class CircularResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'circular' => new CircularResource(null),
        ];
    }
}
