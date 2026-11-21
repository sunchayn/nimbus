<?php

declare(strict_types=1);

namespace Sunchayn\Nimbus\Tests\Integration\Mcp;

use PHPUnit\Framework\Attributes\CoversClass;
use Sunchayn\Nimbus\Modules\Mcp\Actions\ListSavedRequestsAction;
use Sunchayn\Nimbus\Modules\Mcp\Server\Tools\ListSavedRequestsTool;
use Sunchayn\Nimbus\Modules\Workspace\Exceptions\WorkspaceException;
use Sunchayn\Nimbus\Tests\Concerns\InteractsWithMcpServer;
use Sunchayn\Nimbus\Tests\Concerns\UsesTemporaryWorkspace;
use Sunchayn\Nimbus\Tests\TestCase;

#[CoversClass(ListSavedRequestsTool::class)]
class NimbusMcpListSavedRequestsToolTest extends TestCase
{
    use InteractsWithMcpServer;
    use UsesTemporaryWorkspace;

    protected function setUp(): void
    {
        parent::setUp();

        $this->enableWorkspace();
    }

    protected function tearDown(): void
    {
        $this->tearDownWorkspace();

        parent::tearDown();
    }

    public function test_it_passes_the_folder_to_the_list_requests_action(): void
    {
        // Arrange

        $listSavedRequestsActionMock = $this->mock(ListSavedRequestsAction::class);

        // Anticipate

        $listSavedRequestsActionMock
            ->shouldReceive('execute')
            ->andReturn(
                [
                    'count' => 0,
                    'folders' => [],
                    'requests' => [],
                ],
            );

        // Act

        $this->callTool(
            name: 'nimbus_list_saved_requests',
            arguments: [
                'folder' => 'users',
            ],
        );

        // Assert

        $listSavedRequestsActionMock
            ->shouldHaveReceived('execute')
            ->once()
            ->with('users');
    }

    public function test_it_returns_the_listed_requests(): void
    {
        // Arrange

        $listSavedRequestsActionMock = $this->mock(ListSavedRequestsAction::class);

        // Anticipate

        $listSavedRequestsActionMock
            ->shouldReceive('execute')
            ->andReturn(
                [
                    'count' => 1,
                    'folders' => ['users'],
                    'requests' => [
                        [
                            'id' => 'request-1',
                            'name' => 'Create user',
                        ],
                    ],
                ],
            );

        // Act

        $response = $this->callTool(name: 'nimbus_list_saved_requests');

        // Assert

        $response
            ->assertOk()
            ->assertSee('Create user');
    }

    public function test_it_reports_a_disabled_workspace_as_an_error(): void
    {
        // Arrange

        $listSavedRequestsActionMock = $this->mock(ListSavedRequestsAction::class);

        // Anticipate

        $listSavedRequestsActionMock
            ->shouldReceive('execute')
            ->andThrow(WorkspaceException::disabled());

        // Act

        $response = $this->callTool(name: 'nimbus_list_saved_requests');

        // Assert

        $response
            ->assertHasErrors()
            ->assertSee('INVALID_INPUT');
    }

    public function test_it_integrates(): void
    {
        // Arrange

        $this->saveRequest(
            savedRequest: $this->savedRequest(
                id: 'request-1',
                name: 'Create user',
            ),
            hash: null,
            folder: 'users',
        );

        // Act

        $listedRequests = $this->callTool(
            name: 'nimbus_list_saved_requests',
            arguments: [
                'folder' => 'users',
            ],
        );

        $listedRequestsElsewhere = $this->callTool(
            name: 'nimbus_list_saved_requests',
            arguments: [
                'folder' => 'other',
            ],
        );

        // Assert

        $listedRequests
            ->assertOk()
            ->assertSee('Create user');

        $listedRequestsElsewhere
            ->assertOk()
            ->assertDontSee('Create user');
    }
}
