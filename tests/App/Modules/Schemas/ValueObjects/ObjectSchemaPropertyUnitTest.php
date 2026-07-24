<?php

declare(strict_types=1);

namespace Sunchayn\Nimbus\Tests\App\Modules\Schemas\ValueObjects;

use Generator;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Sunchayn\Nimbus\Modules\Schemas\Enums\SchemaPropertyType;
use Sunchayn\Nimbus\Modules\Schemas\ValueObjects\ObjectSchemaProperty;
use Sunchayn\Nimbus\Modules\Schemas\ValueObjects\Schema;
use Sunchayn\Nimbus\Modules\Schemas\ValueObjects\StringSchemaProperty;

#[CoversClass(ObjectSchemaProperty::class)]
class ObjectSchemaPropertyUnitTest extends TestCase
{
    public function test_it_gets_basic_properties(): void
    {
        // Arrange

        $properties = new Schema([new StringSchemaProperty(name: 'id')]);

        // Act

        $property = new ObjectSchemaProperty(
            name: 'user',
            required: true,
            nullable: true,
            schema: $properties,
            additionalProperties: true
        );

        // Assert

        $this->assertEquals('user', $property->getName());
        $this->assertTrue($property->isRequired());
        $this->assertTrue($property->isNullable());
        $this->assertEquals(SchemaPropertyType::OBJECT, $property->getType());
        $this->assertSame($properties, $property->getPropertiesSchema());
    }

    #[DataProvider('jsonSchemaDataProvider')]
    public function test_it_converts_to_json_schema(ObjectSchemaProperty $property, array $expected): void
    {
        // Act

        $result = $property->toJsonSchema();

        // Assert

        $this->assertEquals($expected, $result);
    }

    public static function jsonSchemaDataProvider(): Generator
    {
        yield 'empty object' => [
            'property' => new ObjectSchemaProperty(name: 'data'),
            'expected' => [
                'type' => 'object',
                'additionalProperties' => false,
            ],
        ];

        yield 'nullable empty object' => [
            'property' => new ObjectSchemaProperty(name: 'data', nullable: true),
            'expected' => [
                'type' => ['object', 'null'],
                'additionalProperties' => false,
            ],
        ];

        yield 'object with properties' => [
            'property' => new ObjectSchemaProperty(
                name: 'user',
                schema: new Schema([
                    new StringSchemaProperty(name: 'name', required: true),
                    new StringSchemaProperty(name: 'email'),
                ])
            ),
            'expected' => [
                'type' => 'object',
                'properties' => [
                    'name' => ['type' => 'string'],
                    'email' => ['type' => 'string'],
                ],
                'required' => ['name'],
                'additionalProperties' => false,
            ],
        ];

        yield 'object with additionalProperties true' => [
            'property' => new ObjectSchemaProperty(
                name: 'meta',
                additionalProperties: true
            ),
            'expected' => [
                'type' => 'object',
                'additionalProperties' => true,
            ],
        ];
    }
}
