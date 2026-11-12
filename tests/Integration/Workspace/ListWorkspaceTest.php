<?php

declare(strict_types=1);

namespace Sunchayn\Nimbus\Tests\Integration\Workspace;

use PHPUnit\Framework\Attributes\CoversClass;
use Sunchayn\Nimbus\Http\Api\Workspace\Controllers\ListWorkspaceController;
use Sunchayn\Nimbus\Http\Api\Workspace\Resources\WorkspaceRequestResource;
use Sunchayn\Nimbus\Http\Api\Workspace\Resources\WorkspaceResource;
use Sunchayn\Nimbus\Modules\Workspace\Actions\ScanWorkspaceAction;
use Sunchayn\Nimbus\Modules\Workspace\Collections\WorkspaceEntriesCollection;
use Sunchayn\Nimbus\Modules\Workspace\DataTransferObjects\ManifestFile;
use Sunchayn\Nimbus\Modules\Workspace\DataTransferObjects\SavedRequest;
use Sunchayn\Nimbus\Modules\Workspace\DataTransferObjects\WorkspaceEntry;
use Sunchayn\Nimbus\Modules\Workspace\DataTransferObjects\WorkspaceScanResult;
use Sunchayn\Nimbus\Modules\Workspace\ValueObjects\FolderPath;
use Sunchayn\Nimbus\Tests\Concerns\UsesTemporaryWorkspace;
use Sunchayn\Nimbus\Tests\TestCase;

#[CoversClass(ListWorkspaceController::class)]
#[CoversClass(WorkspaceResource::class)]
#[CoversClass(WorkspaceRequestResource::class)]
class ListWorkspaceTest extends TestCase
{
    use UsesTemporaryWorkspace;

    protected function tearDown(): void
    {
        $this->tearDownWorkspace();

        parent::tearDown();
    }

    public function test_it_lists_the_folders_and_the_request_summaries(): void
    {
        // Arrange

        $this->enableWorkspace();

        $requestFile = $this->requestFile(
            $this->savedRequest(),
            hash: 'a1b2c3d4e5f6a7b8',
            folder: 'users',
        );

        $scanSpy = $this->spy(ScanWorkspaceAction::class);

        // Anticipate

        $scanSpy
            ->shouldReceive('execute')
            ->andReturn(
                new WorkspaceScanResult(
                    manifest: new ManifestFile,
                    entries: WorkspaceEntriesCollection::make([
                        WorkspaceEntry::folder(FolderPath::from('users')),
                        WorkspaceEntry::request($requestFile),
                    ]),
                ),
            );

        // Act

        $response = $this->getJson(route('nimbus.api.workspace.index'));

        // Assert

        $response
            ->assertOk()
            ->assertExactJson([
                'folders' => ['users'],
                'requests' => [
                    [
                        'id' => 'request-1234',
                        'name' => 'Create user',
                        'method' => 'POST',
                        'endpoint' => '{{base_url}}/api/users',
                        'route' => 'users.store',
                        'file' => 'requests/users/create-user.request.json',
                        'folder' => 'users',
                        'hash' => 'a1b2c3d4e5f6a7b8',
                    ],
                ],
            ]);

        $scanSpy->shouldHaveReceived('execute')->once();
    }

    public function test_it_lists_nothing_for_an_empty_workspace(): void
    {
        // Arrange

        $this->enableWorkspace();

        // Act

        $response = $this->getJson(route('nimbus.api.workspace.index'));

        // Assert

        $response
            ->assertOk()
            ->assertExactJson([
                'folders' => [],
                'requests' => [],
            ]);
    }

    public function test_it_keeps_the_order_of_the_entries(): void
    {
        // Arrange

        $this->enableWorkspace();

        $scanSpy = $this->spy(ScanWorkspaceAction::class);

        // Anticipate

        $scanSpy
            ->shouldReceive('execute')
            ->andReturn(
                new WorkspaceScanResult(
                    manifest: new ManifestFile,
                    entries: WorkspaceEntriesCollection::make([
                        WorkspaceEntry::folder(FolderPath::from('zulu')),
                        WorkspaceEntry::folder(FolderPath::from('alpha')),
                        WorkspaceEntry::request($this->requestFile($this->savedRequest(id: 'request-2', name: 'Beta'))),
                        WorkspaceEntry::request($this->requestFile($this->savedRequest(id: 'request-1', name: 'Alpha'))),
                    ]),
                ),
            );

        // Act

        $response = $this->getJson(route('nimbus.api.workspace.index'));

        // Assert

        $response->assertOk();

        $this->assertSame(
            ['zulu', 'alpha'],
            $response->json('folders'),
        );

        $this->assertSame(
            ['request-2', 'request-1'],
            $response->json('requests.*.id'),
        );
    }

    public function test_it_gives_an_empty_folder_and_a_null_route_for_a_request_at_the_root(): void
    {
        // Arrange

        $this->enableWorkspace();

        $this->saveRequest(
            SavedRequest::fromArray([
                'id' => 'request-1234',
                'name' => 'Ping',
                'method' => 'get',
                'endpoint' => '{{base_url}}/ping',
            ]),
            hash: null,
            folder: '',
        );

        // Act

        $response = $this->getJson(route('nimbus.api.workspace.index'));

        // Assert

        $response->assertOk();

        $this->assertSame(
            '',
            $response->json('requests.0.folder'),
        );

        $this->assertSame(
            'requests/ping.request.json',
            $response->json('requests.0.file'),
        );

        $this->assertNull($response->json('requests.0.route'));
    }

    public function test_it_is_not_found_when_the_workspace_is_disabled(): void
    {
        // Act

        $response = $this->getJson(route('nimbus.api.workspace.index'));

        // Assert

        $response
            ->assertNotFound()
            ->assertExactJson([
                'message' => 'The workspace is disabled.',
            ]);
    }

    public function test_it_integrates(): void
    {
        // Arrange

        $this->enableWorkspace();

        $this->createFolder('archive');

        $requestFile = $this->saveRequest(
            $this->savedRequest(),
            hash: null,
            folder: 'users',
        );

        // Act

        $response = $this->getJson(route('nimbus.api.workspace.index'));

        // Assert

        $response
            ->assertOk()
            ->assertExactJson([
                'folders' => ['archive', 'users'],
                'requests' => [
                    [
                        'id' => 'request-1234',
                        'name' => 'Create user',
                        'method' => 'POST',
                        'endpoint' => '{{base_url}}/api/users',
                        'route' => 'users.store',
                        'file' => 'requests/users/create-user.request.json',
                        'folder' => 'users',
                        'hash' => $requestFile->hash,
                    ],
                ],
            ]);
    }
}
