<?php

declare(strict_types=1);

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
        // Arrange

        $property = new StringSchemaProperty(name: 'first_name', required: true, nullable: true);

        // Assert

        $this->assertEquals('first_name', $property->getName());
        $this->assertTrue($property->isRequired());
        $this->assertTrue($property->isNullable());
        $this->assertEquals(SchemaPropertyType::STRING, $property->getType());
    }

    public function test_it_marks_a_copy_as_base64_content(): void
    {
        // Arrange

        $property = new StringSchemaProperty(name: 'content', required: true, minLength: 2, maxLength: 9);

        // Act

        $marked = $property->withContentEncoding(StringSchemaProperty::CONTENT_ENCODING_BASE64);

        // Assert

        $this->assertNotSame($property, $marked);
        $this->assertArrayNotHasKey('contentEncoding', $property->toJsonSchema());
        $this->assertEquals(
            ['type' => 'string', 'minLength' => 2, 'maxLength' => 9, 'contentEncoding' => 'base64'],
            $marked->toJsonSchema(),
        );
        $this->assertTrue($marked->isRequired());
    }

    #[DataProvider('unconstrainedTextDataProvider')]
    public function test_it_tells_whether_it_is_unconstrained_text(StringSchemaProperty $property, bool $expected): void
    {
        // Act & Assert

        $this->assertSame($expected, $property->isUnconstrainedText());
    }

    public static function unconstrainedTextDataProvider(): Generator
    {
        yield 'plain string' => [
            'property' => new StringSchemaProperty(name: 'content', maxLength: 10),
            'expected' => true,
        ];

        yield 'with a format' => [
            'property' => new StringSchemaProperty(name: 'content', stringFormat: StringFormat::EMAIL),
            'expected' => false,
        ];

        yield 'with an enum' => [
            'property' => new StringSchemaProperty(name: 'content', enum: ['a']),
            'expected' => false,
        ];

        yield 'with a constant' => [
            'property' => new StringSchemaProperty(name: 'content', const: 'a'),
            'expected' => false,
        ];

        yield 'already encoded' => [
            'property' => new StringSchemaProperty(name: 'content', contentEncoding: 'base64'),
            'expected' => false,
        ];
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
        yield 'base64 content' => [
            'property' => new StringSchemaProperty(name: 'content', contentEncoding: 'base64'),
            'expected' => [
                'type' => 'string',
                'contentEncoding' => 'base64',
            ],
        ];

        yield 'basic string' => [
            'property' => new StringSchemaProperty(name: 'username'),
            'expected' => [
                'type' => 'string',
            ],
        ];

        yield 'nullable string' => [
            'property' => new StringSchemaProperty(name: 'description', nullable: true),
            'expected' => [
                'type' => ['string', 'null'],
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
