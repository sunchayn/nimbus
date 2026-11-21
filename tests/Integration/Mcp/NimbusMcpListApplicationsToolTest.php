<?php

declare(strict_types=1);

namespace Sunchayn\Nimbus\Tests\Integration\Mcp;

use PHPUnit\Framework\Attributes\CoversClass;
use Sunchayn\Nimbus\Modules\Mcp\Actions\ListApplicationsAction;
use Sunchayn\Nimbus\Modules\Mcp\Exceptions\McpToolException;
use Sunchayn\Nimbus\Modules\Mcp\Server\Tools\ListApplicationsTool;
use Sunchayn\Nimbus\Tests\Concerns\InteractsWithMcpServer;
use Sunchayn\Nimbus\Tests\TestCase;

#[CoversClass(ListApplicationsTool::class)]
class NimbusMcpListApplicationsToolTest extends TestCase
{
    use InteractsWithMcpServer;

    public function test_it_returns_the_applications(): void
    {
        // Arrange

        $listApplicationsActionMock = $this->mock(ListApplicationsAction::class);

        // Anticipate

        $listApplicationsActionMock
            ->shouldReceive('execute')
            ->andReturn(
                [
                    'applications' => [
                        [
                            'key' => 'main',
                            'name' => 'Main Application',
                        ],
                    ],
                ],
            );

        // Act

        $response = $this->callTool(name: 'nimbus_list_applications');

        // Assert

        $listApplicationsActionMock
            ->shouldHaveReceived('execute')
            ->once()
            ->withNoArgs();

        $response
            ->assertOk()
            ->assertSee('Main Application');
    }

    public function test_it_is_available_at_the_read_level(): void
    {
        // Arrange

        config()->set('nimbus.mcp.level', 'read');

        // Act

        $tools = $this->listTools();

        // Assert

        $tools->assertRegistered('nimbus_list_applications');
    }

    public function test_it_reports_a_failure_as_an_error(): void
    {
        // Arrange

        $listApplicationsActionMock = $this->mock(ListApplicationsAction::class);

        $unknownApplicationException = McpToolException::becauseApplicationIsUnknown(
            application: 'nope',
            known: ['main'],
        );

        // Anticipate

        $listApplicationsActionMock
            ->shouldReceive('execute')
            ->andThrow($unknownApplicationException);

        // Act

        $response = $this->callTool(name: 'nimbus_list_applications');

        // Assert

        $response
            ->assertHasErrors()
            ->assertSee('is not configured');
    }

    public function test_it_integrates(): void
    {
        // Act

        $response = $this->callTool(name: 'nimbus_list_applications');

        // Assert

        $response
            ->assertOk()
            ->assertSee('main');
    }
}
