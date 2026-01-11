<?php

namespace Sunchayn\Nimbus\Tests\App\Modules\Schemas\ValueObjects;

use Generator;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Sunchayn\Nimbus\Modules\Schemas\Enums\SchemaPropertyType;
use Sunchayn\Nimbus\Modules\Schemas\ValueObjects\IntegerSchemaProperty;

#[CoversClass(IntegerSchemaProperty::class)]
class IntegerSchemaPropertyUnitTest extends TestCase
{
    public function test_it_gets_basic_properties(): void
    {
        // Arrange & Act

        $property = new IntegerSchemaProperty(name: 'age', required: true);

        // Assert

        $this->assertEquals('age', $property->getName());
        $this->assertTrue($property->isRequired());
        $this->assertEquals(SchemaPropertyType::INTEGER, $property->getType());
    }

    #[DataProvider('jsonSchemaDataProvider')]
    public function test_it_converts_to_json_schema(IntegerSchemaProperty $property, array $expected): void
    {
        // Act

        $result = $property->toJsonSchema();

        // Assert

        $this->assertEquals($expected, $result);
    }

    public static function jsonSchemaDataProvider(): Generator
    {
        yield 'basic integer' => [
            'property' => new IntegerSchemaProperty(name: 'count'),
            'expected' => [
                'type' => 'integer',
            ],
        ];

        yield 'integer with minimum' => [
            'property' => new IntegerSchemaProperty(name: 'age', minimum: 18),
            'expected' => [
                'type' => 'integer',
                'minimum' => 18,
            ],
        ];

        yield 'integer with maximum' => [
            'property' => new IntegerSchemaProperty(name: 'score', maximum: 100),
            'expected' => [
                'type' => 'integer',
                'maximum' => 100,
            ],
        ];

        yield 'integer with range' => [
            'property' => new IntegerSchemaProperty(name: 'range', minimum: 0, maximum: 10),
            'expected' => [
                'type' => 'integer',
                'minimum' => 0,
                'maximum' => 10,
            ],
        ];

        yield 'integer with enum' => [
            'property' => new IntegerSchemaProperty(name: 'level', enum: [1, 2, 3]),
            'expected' => [
                'type' => 'integer',
                'enum' => [1, 2, 3],
            ],
        ];
    }
}
