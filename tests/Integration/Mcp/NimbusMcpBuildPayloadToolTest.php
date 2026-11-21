<?php

declare(strict_types=1);

namespace Sunchayn\Nimbus\Tests\Integration\Mcp;

use PHPUnit\Framework\Attributes\CoversClass;
use Sunchayn\Nimbus\Modules\Mcp\Actions\BuildPayloadAction;
use Sunchayn\Nimbus\Modules\Mcp\Server\Tools\BuildPayloadTool;
use Sunchayn\Nimbus\Tests\App\Modules\Mcp\Concerns\FakesExtractedRoutes;
use Sunchayn\Nimbus\Tests\Concerns\InteractsWithMcpServer;
use Sunchayn\Nimbus\Tests\TestCase;

#[CoversClass(BuildPayloadTool::class)]
class NimbusMcpBuildPayloadToolTest extends TestCase
{
    use FakesExtractedRoutes;
    use InteractsWithMcpServer;

    public function test_it_passes_the_payload_options_to_the_build_payload_action(): void
    {
        // Arrange

        $buildPayloadActionMock = $this->mock(BuildPayloadAction::class);

        // Anticipate

        $buildPayloadActionMock
            ->shouldReceive('execute')
            ->andReturn(
                [
                    'route' => 'POST api/users',
                    'mode' => 'minimal',
                    'payload' => [],
                    'notes' => [],
                ],
            );

        // Act

        $this->callTool(
            name: 'nimbus_build_payload',
            arguments: [
                'route' => 'POST api/users',
                'mode' => 'minimal',
                'overrides' => [
                    'name' => 'Ada',
                ],
            ],
        );

        // Assert

        $buildPayloadActionMock
            ->shouldHaveReceived('execute')
            ->once()
            ->withArgs(function (string $route, string $mode, array $overrides, ?string $application): bool {
                $this->assertSame(
                    'POST api/users',
                    $route,
                );

                $this->assertSame(
                    'minimal',
                    $mode,
                );

                $this->assertSame(
                    ['name' => 'Ada'],
                    $overrides,
                );

                return true;
            });
    }

    public function test_it_integrates(): void
    {
        // Arrange

        $this->fakeExtractedRoutes();

        // Act

        $response = $this->callTool(
            name: 'nimbus_build_payload',
            arguments: [
                'route' => 'POST api/users',
                'mode' => 'minimal',
            ],
        );

        // Assert

        $response
            ->assertOk()
            ->assertSee('user@example.com');
    }
}
