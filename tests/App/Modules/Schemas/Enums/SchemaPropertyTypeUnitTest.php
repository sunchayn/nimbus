<?php

declare(strict_types=1);

namespace Sunchayn\Nimbus\Tests\App\Modules\Schemas\Enums;

use Generator;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Sunchayn\Nimbus\Modules\Schemas\Enums\SchemaPropertyType;

#[CoversClass(SchemaPropertyType::class)]
class SchemaPropertyTypeUnitTest extends TestCase
{
    #[DataProvider('phpScalarProvider')]
    public function test_maps_from_php_scalar_types(string $input, ?SchemaPropertyType $expected): void
    {
        // Arrange & Act

        $result = SchemaPropertyType::STRING->fromPhpScalar($input);

        // Assert

        $this->assertSame($expected, $result);
    }

    public static function phpScalarProvider(): Generator
    {
        yield 'string type' => [
            'input' => 'string',
            'expected' => SchemaPropertyType::STRING,
        ];

        yield 'int type' => [
            'input' => 'int',
            'expected' => SchemaPropertyType::INTEGER,
        ];

        yield 'float type' => [
            'input' => 'float',
            'expected' => SchemaPropertyType::NUMBER,
        ];

        yield 'bool type' => [
            'input' => 'bool',
            'expected' => SchemaPropertyType::BOOLEAN,
        ];

        yield 'unsupported type' => [
            'input' => 'array',
            'expected' => null,
        ];
    }
}
