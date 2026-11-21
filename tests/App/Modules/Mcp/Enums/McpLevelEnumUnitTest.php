<?php

declare(strict_types=1);

namespace Sunchayn\Nimbus\Tests\App\Modules\Mcp\Enums;

use Generator;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Sunchayn\Nimbus\Modules\Mcp\Enums\McpLevelEnum;

#[CoversClass(McpLevelEnum::class)]
class McpLevelEnumUnitTest extends TestCase
{
    #[DataProvider('includedLevels')]
    public function test_it_includes_the_levels_below_each_level(McpLevelEnum $level, McpLevelEnum $required): void
    {
        // Act & Assert

        $this->assertTrue($level->includes($required));
    }

    public static function includedLevels(): Generator
    {
        yield 'write includes read' => [
            'level' => McpLevelEnum::Write,
            'required' => McpLevelEnum::Read,
        ];

        yield 'write includes send' => [
            'level' => McpLevelEnum::Write,
            'required' => McpLevelEnum::Send,
        ];

        yield 'send includes itself' => [
            'level' => McpLevelEnum::Send,
            'required' => McpLevelEnum::Send,
        ];
    }

    #[DataProvider('excludedLevels')]
    public function test_it_excludes_the_levels_above_each_level(McpLevelEnum $level, McpLevelEnum $required): void
    {
        // Act & Assert

        $this->assertFalse($level->includes($required));
    }

    public static function excludedLevels(): Generator
    {
        yield 'read excludes send' => [
            'level' => McpLevelEnum::Read,
            'required' => McpLevelEnum::Send,
        ];

        yield 'read excludes write' => [
            'level' => McpLevelEnum::Read,
            'required' => McpLevelEnum::Write,
        ];

        yield 'send excludes write' => [
            'level' => McpLevelEnum::Send,
            'required' => McpLevelEnum::Write,
        ];
    }
}
