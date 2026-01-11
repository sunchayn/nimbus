<?php

namespace Sunchayn\Nimbus\Tests\App\Modules\Schemas\ValueObjects;

use Generator;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Sunchayn\Nimbus\Modules\Schemas\Enums\SchemaPropertyType;
use Sunchayn\Nimbus\Modules\Schemas\ValueObjects\BooleanSchemaProperty;

#[CoversClass(BooleanSchemaProperty::class)]
class BooleanSchemaPropertyUnitTest extends TestCase
{
    public function test_it_gets_basic_properties(): void
    {
        // Arrange & Act

        $property = new BooleanSchemaProperty(name: 'is_active', required: true);

        // Assert

        $this->assertEquals('is_active', $property->getName());
        $this->assertTrue($property->isRequired());
        $this->assertEquals(SchemaPropertyType::BOOLEAN, $property->getType());
    }

    #[DataProvider('jsonSchemaDataProvider')]
    public function test_it_converts_to_json_schema(BooleanSchemaProperty $property, array $expected): void
    {
        // Act

        $result = $property->toJsonSchema();

        // Assert

        $this->assertEquals($expected, $result);
    }

    public static function jsonSchemaDataProvider(): Generator
    {
        yield 'boolean property' => [
            'property' => new BooleanSchemaProperty(name: 'enabled'),
            'expected' => [
                'type' => 'boolean',
            ],
        ];
    }
}
