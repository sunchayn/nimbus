<?php

namespace Sunchayn\Nimbus\Tests\App\Modules\Config\Enums;

use Generator;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use Sunchayn\Nimbus\Modules\Config\Enums\RoutesProcessingStrategyEnum;
use Sunchayn\Nimbus\Tests\TestCase;

#[CoversClass(RoutesProcessingStrategyEnum::class)]
class RoutesProcessingStrategyEnumUnitTest extends TestCase
{
    #[DataProvider('strategyAliasProvider')]
    public function test_it_reconciles_route_processing_strategy_aliases(mixed $input, ?RoutesProcessingStrategyEnum $expected): void
    {
        // Act & Assert

        $this->assertEquals($expected, RoutesProcessingStrategyEnum::tryFromAlias($input));
    }

    public static function strategyAliasProvider(): Generator
    {
        yield 'auto_detect alias' => [
            'input' => 'auto_detect',
            'expected' => RoutesProcessingStrategyEnum::AutoDetect,
        ];

        yield 'openapi alias' => [
            'input' => 'openapi',
            'expected' => RoutesProcessingStrategyEnum::OpenAPI,
        ];

        yield 'enum instance' => [
            'input' => RoutesProcessingStrategyEnum::OpenAPI,
            'expected' => RoutesProcessingStrategyEnum::OpenAPI,
        ];

        yield 'invalid strategy' => [
            'input' => 'invalid_strategy',
            'expected' => null,
        ];

        yield 'non-string input' => [
            'input' => 123,
            'expected' => null,
        ];
    }
}
