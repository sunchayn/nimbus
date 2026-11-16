<?php

declare(strict_types=1);

namespace Sunchayn\Nimbus\Tests\Integration\Workspace;

use PHPUnit\Framework\Attributes\CoversClass;
use Sunchayn\Nimbus\Http\Api\Workspace\Controllers\DeleteWorkspaceRequestController;
use Sunchayn\Nimbus\Modules\Workspace\Actions\DeleteWorkspaceRequestAction;
use Sunchayn\Nimbus\Modules\Workspace\Exceptions\WorkspaceException;
use Sunchayn\Nimbus\Tests\Concerns\UsesTemporaryWorkspace;
use Sunchayn\Nimbus\Tests\TestCase;

#[CoversClass(DeleteWorkspaceRequestController::class)]
class DeleteWorkspaceRequestTest extends TestCase
{
    use UsesTemporaryWorkspace;

    protected function tearDown(): void
    {
        $this->tearDownWorkspace();

        parent::tearDown();
    }

    public function test_it_passes_the_id_to_the_action(): void
    {
        // Arrange

        $actionSpy = $this->spy(DeleteWorkspaceRequestAction::class);

        // Act

        $response = $this->deleteJson(route(
            'nimbus.api.workspace.requests.destroy',
            'request-1234',
        ));

        // Assert

        $response->assertNoContent();

        $actionSpy
            ->shouldHaveReceived('execute')
            ->once()
            ->withArgs(function (string $id): bool {
                $this->assertSame(
                    'request-1234',
                    $id,
                );

                return true;
            });
    }

    public function test_it_reports_a_request_that_does_not_exist(): void
    {
        // Arrange

        $actionSpy = $this->spy(DeleteWorkspaceRequestAction::class);

        // Anticipate

        $actionSpy
            ->shouldReceive('execute')
            ->andThrow(WorkspaceException::requestNotFound('request-9999'));

        // Act

        $response = $this->deleteJson(route(
            'nimbus.api.workspace.requests.destroy',
            'request-9999',
        ));

        // Assert

        $response
            ->assertNotFound()
            ->assertExactJson([
                'message' => 'The request `request-9999` does not exist in the workspace.',
            ]);
    }

    public function test_it_integrates(): void
    {
        // Arrange

        $this->enableWorkspace();

        $requestFile = $this->saveRequest(
            $this->savedRequest(),
            hash: null,
        );

        // Act

        $response = $this->deleteJson(route(
            'nimbus.api.workspace.requests.destroy',
            'request-1234',
        ));

        // Assert

        $response->assertNoContent();

        $this->assertFileDoesNotExist($requestFile->filepath);

        $this->assertSame(
            [],
            $this->listRequestIds(),
        );
    }
}
