<?php

namespace Sunchayn\Nimbus\Tests\App\Modules\Schemas\ValueObjects;

use Generator;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Sunchayn\Nimbus\Modules\Schemas\Enums\SchemaPropertyType;
use Sunchayn\Nimbus\Modules\Schemas\ValueObjects\NullSchemaProperty;

#[CoversClass(NullSchemaProperty::class)]
class NullSchemaPropertyUnitTest extends TestCase
{
    public function test_it_gets_basic_properties(): void
    {
        // Arrange & Act

        $property = new NullSchemaProperty(name: 'nullable_field', required: false);

        // Assert

        $this->assertEquals('nullable_field', $property->getName());
        $this->assertFalse($property->isRequired());
        $this->assertEquals(SchemaPropertyType::NULL, $property->getType());
    }

    #[DataProvider('jsonSchemaDataProvider')]
    public function test_it_converts_to_json_schema(NullSchemaProperty $property, array $expected): void
    {
        // Act

        $result = $property->toJsonSchema();

        // Assert

        $this->assertEquals($expected, $result);
    }

    public static function jsonSchemaDataProvider(): Generator
    {
        yield 'null property' => [
            'property' => new NullSchemaProperty(name: 'nothing'),
            'expected' => [
                'type' => 'null',
            ],
        ];
    }
}
