<?php

namespace Sunchayn\Nimbus\Tests\App\Modules\Schemas\ValueObjects;

use Generator;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Sunchayn\Nimbus\Modules\Schemas\Enums\SchemaPropertyType;
use Sunchayn\Nimbus\Modules\Schemas\ValueObjects\ArraySchemaProperty;
use Sunchayn\Nimbus\Modules\Schemas\ValueObjects\StringSchemaProperty;

#[CoversClass(ArraySchemaProperty::class)]
class ArraySchemaPropertyUnitTest extends TestCase
{
    public function test_it_gets_basic_properties(): void
    {
        // Arrange

        $items = new StringSchemaProperty(name: 'item');

        // Act

        $property = new ArraySchemaProperty(
            name: 'tags',
            required: true,
            schemaProperty: $items,
            minItems: 1,
            maxItems: 10
        );

        // Assert

        $this->assertEquals('tags', $property->getName());
        $this->assertTrue($property->isRequired());
        $this->assertEquals(SchemaPropertyType::ARRAY, $property->getType());
        $this->assertSame($items, $property->getItemsSchema());
    }

    #[DataProvider('jsonSchemaDataProvider')]
    public function test_it_converts_to_json_schema(ArraySchemaProperty $property, array $expected): void
    {
        // Act

        $result = $property->toJsonSchema();

        // Assert

        $this->assertEquals($expected, $result);
    }

    public static function jsonSchemaDataProvider(): Generator
    {
        yield 'basic array' => [
            'property' => new ArraySchemaProperty(name: 'list'),
            'expected' => [
                'type' => 'array',
            ],
        ];

        yield 'array with string items' => [
            'property' => new ArraySchemaProperty(
                name: 'tags',
                schemaProperty: new StringSchemaProperty(name: 'tag')
            ),
            'expected' => [
                'type' => 'array',
                'items' => [
                    'type' => 'string',
                ],
            ],
        ];

        yield 'array with size constraints' => [
            'property' => new ArraySchemaProperty(
                name: 'items',
                minItems: 2,
                maxItems: 5
            ),
            'expected' => [
                'type' => 'array',
                'minItems' => 2,
                'maxItems' => 5,
            ],
        ];

        yield 'array with constraints and items' => [
            'property' => new ArraySchemaProperty(
                name: 'tags',
                schemaProperty: new StringSchemaProperty(name: 'tag'),
                minItems: 1
            ),
            'expected' => [
                'type' => 'array',
                'items' => [
                    'type' => 'string',
                ],
                'minItems' => 1,
            ],
        ];
    }
}
