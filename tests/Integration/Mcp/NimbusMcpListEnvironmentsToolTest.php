<?php

declare(strict_types=1);

namespace Sunchayn\Nimbus\Tests\Integration\Mcp;

use PHPUnit\Framework\Attributes\CoversClass;
use Sunchayn\Nimbus\Modules\Mcp\Actions\ListEnvironmentsAction;
use Sunchayn\Nimbus\Modules\Mcp\Exceptions\McpToolException;
use Sunchayn\Nimbus\Modules\Mcp\Server\Tools\ListEnvironmentsTool;
use Sunchayn\Nimbus\Tests\Concerns\InteractsWithMcpServer;
use Sunchayn\Nimbus\Tests\TestCase;

#[CoversClass(ListEnvironmentsTool::class)]
class NimbusMcpListEnvironmentsToolTest extends TestCase
{
    use InteractsWithMcpServer;

    public function test_it_returns_the_environments(): void
    {
        // Arrange

        $listEnvironmentsActionMock = $this->mock(ListEnvironmentsAction::class);

        // Anticipate

        $listEnvironmentsActionMock
            ->shouldReceive('execute')
            ->andReturn(
                [
                    'environments' => [
                        [
                            'id' => 'current',
                            'name' => 'Current host',
                        ],
                    ],
                ],
            );

        // Act

        $response = $this->callTool(name: 'nimbus_list_environments');

        // Assert

        $listEnvironmentsActionMock
            ->shouldHaveReceived('execute')
            ->once()
            ->withNoArgs();

        $response
            ->assertOk()
            ->assertSee('Current host');
    }

    public function test_it_reports_a_failure_as_an_error(): void
    {
        // Arrange

        $listEnvironmentsActionMock = $this->mock(ListEnvironmentsAction::class);

        $requestFailedException = McpToolException::becauseRequestFailed(reason: 'The environments are unreadable.');

        // Anticipate

        $listEnvironmentsActionMock
            ->shouldReceive('execute')
            ->andThrow($requestFailedException);

        // Act

        $response = $this->callTool(name: 'nimbus_list_environments');

        // Assert

        $response
            ->assertHasErrors()
            ->assertSee('REQUEST_FAILED');
    }

    public function test_it_integrates(): void
    {
        // Act

        $response = $this->callTool(name: 'nimbus_list_environments');

        // Assert

        $response
            ->assertOk()
            ->assertSee('current');
    }
}
