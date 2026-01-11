<?php

namespace Sunchayn\Nimbus\Tests\App\Modules\Schemas\ValueObjects;

use Generator;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Sunchayn\Nimbus\Modules\Schemas\Enums\SchemaPropertyType;
use Sunchayn\Nimbus\Modules\Schemas\Enums\StringFormat;
use Sunchayn\Nimbus\Modules\Schemas\ValueObjects\StringSchemaProperty;

#[CoversClass(StringSchemaProperty::class)]
class StringSchemaPropertyUnitTest extends TestCase
{
    public function test_it_gets_basic_properties(): void
    {
        // Arrange & Act

        $property = new StringSchemaProperty(name: 'first_name', required: true);

        // Assert

        $this->assertEquals('first_name', $property->getName());
        $this->assertTrue($property->isRequired());
        $this->assertEquals(SchemaPropertyType::STRING, $property->getType());
    }

    #[DataProvider('jsonSchemaDataProvider')]
    public function test_it_converts_to_json_schema(StringSchemaProperty $property, array $expected): void
    {
        // Act

        $result = $property->toJsonSchema();

        // Assert

        $this->assertEquals($expected, $result);
    }

    public static function jsonSchemaDataProvider(): Generator
    {
        yield 'basic string' => [
            'property' => new StringSchemaProperty(name: 'username'),
            'expected' => [
                'type' => 'string',
            ],
        ];

        yield 'string with format' => [
            'property' => new StringSchemaProperty(name: 'email', stringFormat: StringFormat::EMAIL),
            'expected' => [
                'type' => 'string',
                'format' => 'email',
            ],
        ];

        yield 'string with enum' => [
            'property' => new StringSchemaProperty(name: 'role', enum: ['admin', 'user']),
            'expected' => [
                'type' => 'string',
                'enum' => ['admin', 'user'],
            ],
        ];

        yield 'string with min and max length' => [
            'property' => new StringSchemaProperty(name: 'password', minLength: 8, maxLength: 32),
            'expected' => [
                'type' => 'string',
                'minLength' => 8,
                'maxLength' => 32,
            ],
        ];

        yield 'string with pattern' => [
            'property' => new StringSchemaProperty(name: 'code', pattern: '^[A-Z]{3}$'),
            'expected' => [
                'type' => 'string',
                'pattern' => '^[A-Z]{3}$',
            ],
        ];

        yield 'string with const' => [
            'property' => new StringSchemaProperty(name: 'type', const: 'fixed_value'),
            'expected' => [
                'type' => 'string',
                'const' => 'fixed_value',
            ],
        ];

        yield 'string with multiple constraints' => [
            'property' => new StringSchemaProperty(
                name: 'email',
                required: true,
                stringFormat: StringFormat::EMAIL,
                maxLength: 255
            ),
            'expected' => [
                'type' => 'string',
                'format' => 'email',
                'maxLength' => 255,
            ],
        ];
    }
}
