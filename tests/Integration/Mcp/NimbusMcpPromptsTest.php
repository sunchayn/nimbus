<?php

declare(strict_types=1);

namespace Sunchayn\Nimbus\Tests\Integration\Mcp;

use Generator;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use Sunchayn\Nimbus\Modules\Mcp\Server\Prompts\CheckValidationPrompt;
use Sunchayn\Nimbus\Modules\Mcp\Server\Prompts\SmokeTestRoutePrompt;
use Sunchayn\Nimbus\Tests\Concerns\InteractsWithMcpServer;
use Sunchayn\Nimbus\Tests\TestCase;

#[CoversClass(SmokeTestRoutePrompt::class)]
#[CoversClass(CheckValidationPrompt::class)]
class NimbusMcpPromptsTest extends TestCase
{
    use InteractsWithMcpServer;

    #[DataProvider('prompts')]
    public function test_it_serves_the_prompt(string $prompt, string $expectedTool): void
    {
        // Act

        $response = $this->getPrompt(
            name: $prompt,
            arguments: [
                'route' => 'POST api/users',
            ],
        );

        // Assert

        $response
            ->assertOk()
            ->assertSee('POST api/users')
            ->assertSee($expectedTool);
    }

    public static function prompts(): Generator
    {
        yield 'the smoke test prompt' => [
            'prompt' => 'smoke_test_route',
            'expectedTool' => 'nimbus_send_request',
        ];

        yield 'the check validation prompt' => [
            'prompt' => 'check_validation',
            'expectedTool' => 'nimbus_validate_payload',
        ];
    }
}
