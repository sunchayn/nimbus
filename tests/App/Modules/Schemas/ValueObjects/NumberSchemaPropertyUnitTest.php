<?php

namespace Sunchayn\Nimbus\Tests\App\Modules\Schemas\ValueObjects;

use Generator;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Sunchayn\Nimbus\Modules\Schemas\Enums\SchemaPropertyType;
use Sunchayn\Nimbus\Modules\Schemas\ValueObjects\NumberSchemaProperty;

#[CoversClass(NumberSchemaProperty::class)]
class NumberSchemaPropertyUnitTest extends TestCase
{
    public function test_it_gets_basic_properties(): void
    {
        // Arrange & Act

        $property = new NumberSchemaProperty(name: 'price', required: true);

        // Assert

        $this->assertEquals('price', $property->getName());
        $this->assertTrue($property->isRequired());
        $this->assertEquals(SchemaPropertyType::NUMBER, $property->getType());
    }

    #[DataProvider('jsonSchemaDataProvider')]
    public function test_it_converts_to_json_schema(NumberSchemaProperty $property, array $expected): void
    {
        // Act

        $result = $property->toJsonSchema();

        // Assert

        $this->assertEquals($expected, $result);
    }

    public static function jsonSchemaDataProvider(): Generator
    {
        yield 'basic number' => [
            'property' => new NumberSchemaProperty(name: 'weight'),
            'expected' => [
                'type' => 'number',
            ],
        ];

        yield 'number with minimum' => [
            'property' => new NumberSchemaProperty(name: 'rating', minimum: 0.0),
            'expected' => [
                'type' => 'number',
                'minimum' => 0.0,
            ],
        ];

        yield 'number with maximum' => [
            'property' => new NumberSchemaProperty(name: 'percent', maximum: 100.0),
            'expected' => [
                'type' => 'number',
                'maximum' => 100.0,
            ],
        ];

        yield 'number with range' => [
            'property' => new NumberSchemaProperty(name: 'coord', minimum: -180.0, maximum: 180.0),
            'expected' => [
                'type' => 'number',
                'minimum' => -180.0,
                'maximum' => 180.0,
            ],
        ];
    }
}
