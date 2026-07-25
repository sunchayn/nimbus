<?php

namespace Sunchayn\Nimbus\Tests\App\Modules\Config;

use Generator;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use Sunchayn\Nimbus\Modules\Config\GlobalHeaderGeneratorTypeEnum;
use Sunchayn\Nimbus\Tests\TestCase;

#[CoversClass(GlobalHeaderGeneratorTypeEnum::class)]
class GlobalHeaderGeneratorTypeEnumUnitTest extends TestCase
{
    #[DataProvider('generatorAliasProvider')]
    public function test_it_reconciles_global_header_generator_aliases(mixed $input, ?GlobalHeaderGeneratorTypeEnum $expected): void
    {
        // Act & Assert

        $this->assertEquals($expected, GlobalHeaderGeneratorTypeEnum::tryFromAlias($input));
    }

    public static function generatorAliasProvider(): Generator
    {
        yield '$uuid alias' => [
            'input' => '$uuid',
            'expected' => GlobalHeaderGeneratorTypeEnum::Uuid,
        ];

        yield '$email alias' => [
            'input' => '$email',
            'expected' => GlobalHeaderGeneratorTypeEnum::Email,
        ];

        yield '$string alias' => [
            'input' => '$string',
            'expected' => GlobalHeaderGeneratorTypeEnum::String,
        ];

        yield 'enum instance' => [
            'input' => GlobalHeaderGeneratorTypeEnum::Uuid,
            'expected' => GlobalHeaderGeneratorTypeEnum::Uuid,
        ];

        yield 'unprefixed string' => [
            'input' => 'uuid',
            'expected' => null,
        ];

        yield 'raw value' => [
            'input' => 'raw_header_value',
            'expected' => null,
        ];

        yield 'non-string input' => [
            'input' => 123,
            'expected' => null,
        ];
    }
}
