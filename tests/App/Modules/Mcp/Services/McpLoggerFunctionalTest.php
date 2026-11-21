<?php

declare(strict_types=1);

namespace Sunchayn\Nimbus\Tests\App\Modules\Mcp\Services;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use Psr\Log\LoggerInterface;
use Psr\Log\LogLevel;
use Sunchayn\Nimbus\Modules\Mcp\Services\McpLogger;
use Sunchayn\Nimbus\Tests\TestCase;

#[CoversClass(McpLogger::class)]
class McpLoggerFunctionalTest extends TestCase
{
    public function test_it_passes_a_warning_to_the_application_log(): void
    {
        // Arrange

        $loggerSpy = $this->spy(LoggerInterface::class);

        $mcpLogger = resolve(McpLogger::class);

        // Act

        $mcpLogger->warning(
            'Something is off.',
            ['tool' => 'nimbus_list_routes'],
        );

        // Assert

        $loggerSpy
            ->shouldHaveReceived('log')
            ->once()
            ->with(
                LogLevel::WARNING,
                'Something is off.',
                ['tool' => 'nimbus_list_routes'],
            );
    }

    #[DataProvider('quietLevelsProvider')]
    public function test_it_drops_the_levels_that_can_hold_tool_arguments(string $level): void
    {
        // Arrange

        $loggerSpy = $this->spy(LoggerInterface::class);

        $mcpLogger = resolve(McpLogger::class);

        // Act

        $mcpLogger->log(
            $level,
            'Executing tool',
            ['arguments' => ['token' => 'secret']],
        );

        // Assert

        $loggerSpy->shouldNotHaveReceived('log');
    }

    public static function quietLevelsProvider(): \Generator
    {
        yield 'debug' => ['level' => LogLevel::DEBUG];

        yield 'info' => ['level' => LogLevel::INFO];

        yield 'notice' => ['level' => LogLevel::NOTICE];
    }
}
