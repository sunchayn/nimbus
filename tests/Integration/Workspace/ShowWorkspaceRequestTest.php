<?php

declare(strict_types=1);

namespace Sunchayn\Nimbus\Tests\Integration\Workspace;

use PHPUnit\Framework\Attributes\CoversClass;
use Sunchayn\Nimbus\Http\Api\Workspace\Controllers\ShowWorkspaceRequestController;
use Sunchayn\Nimbus\Modules\Workspace\Actions\ScanWorkspaceAction;
use Sunchayn\Nimbus\Modules\Workspace\Collections\WorkspaceEntriesCollection;
use Sunchayn\Nimbus\Modules\Workspace\DataTransferObjects\ManifestFile;
use Sunchayn\Nimbus\Modules\Workspace\DataTransferObjects\WorkspaceEntry;
use Sunchayn\Nimbus\Modules\Workspace\DataTransferObjects\WorkspaceScanResult;
use Sunchayn\Nimbus\Tests\Concerns\UsesTemporaryWorkspace;
use Sunchayn\Nimbus\Tests\TestCase;

#[CoversClass(ShowWorkspaceRequestController::class)]
class ShowWorkspaceRequestTest extends TestCase
{
    use UsesTemporaryWorkspace;

    protected function tearDown(): void
    {
        $this->tearDownWorkspace();

        parent::tearDown();
    }

    public function test_it_returns_the_request_and_its_hash(): void
    {
        // Arrange

        $requestFile = $this->requestFile(
            $this->savedRequest(),
            hash: 'a1b2c3d4e5f6a7b8',
        );

        $scanSpy = $this->spy(ScanWorkspaceAction::class);

        // Anticipate

        $workspaceScanResult = new WorkspaceScanResult(
            manifest: new ManifestFile,
            entries: WorkspaceEntriesCollection::make([WorkspaceEntry::request($requestFile)]),
        );

        $scanSpy->shouldReceive('execute')->andReturn($workspaceScanResult);

        // Act

        $response = $this->getJson(route(
            'nimbus.api.workspace.requests.show',
            'request-1234',
        ));

        // Assert

        $response
            ->assertOk()
            ->assertExactJson($requestFile->toArray());

        $scanSpy->shouldHaveReceived('execute')->once();
    }

    public function test_it_reports_a_request_that_does_not_exist(): void
    {
        // Arrange

        $scanSpy = $this->spy(ScanWorkspaceAction::class);

        // Anticipate

        $workspaceScanResult = new WorkspaceScanResult(
            manifest: new ManifestFile,
            entries: WorkspaceEntriesCollection::make([]),
        );

        $scanSpy->shouldReceive('execute')->andReturn($workspaceScanResult);

        // Act

        $response = $this->getJson(route(
            'nimbus.api.workspace.requests.show',
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

        $response = $this->getJson(route(
            'nimbus.api.workspace.requests.show',
            'request-1234',
        ));

        // Assert

        $response
            ->assertOk()
            ->assertExactJson($requestFile->toArray());
    }
}
