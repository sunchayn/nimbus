<?php

declare(strict_types=1);

namespace Sunchayn\Nimbus\Tests\App\Modules\Workspace\Actions;

use PHPUnit\Framework\Attributes\CoversClass;
use Sunchayn\Nimbus\Modules\Workspace\Actions\MoveWorkspaceRequestAction;
use Sunchayn\Nimbus\Modules\Workspace\DataTransferObjects\MoveWorkspaceRequestData;
use Sunchayn\Nimbus\Modules\Workspace\Exceptions\WorkspaceException;
use Sunchayn\Nimbus\Modules\Workspace\ValueObjects\FolderPath;
use Sunchayn\Nimbus\Tests\Concerns\UsesTemporaryWorkspace;
use Sunchayn\Nimbus\Tests\TestCase;

#[CoversClass(MoveWorkspaceRequestAction::class)]
#[CoversClass(WorkspaceException::class)]
class MoveWorkspaceRequestActionFunctionalTest extends TestCase
{
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

    public function test_it_moves_a_request_and_keeps_its_id(): void
    {
        // Arrange

        $stored = $this->saveRequest(
            $this->savedRequest(),
            hash: null,
        );

        $data = $this->moveData(
            id: 'request-1234',
            folder: 'archive/old',
            name: null,
            hash: $stored->hash,
        );

        $action = resolve(MoveWorkspaceRequestAction::class);

        // Act

        $moved = $action->execute(data: $data);

        // Assert

        $this->assertFileDoesNotExist("{$this->workspacePath}/requests/create-user.request.json");
        $this->assertFileExists("{$this->workspacePath}/requests/archive/old/create-user.request.json");

        $this->assertSame(
            'request-1234',
            $this->findRequest('request-1234')->savedRequest->id,
        );

        $this->assertSame(
            $moved->hash,
            $this->findRequest('request-1234')->hash,
        );
    }

    public function test_it_moves_a_request_back_to_the_root(): void
    {
        // Arrange

        $stored = $this->saveRequest(
            $this->savedRequest(),
            hash: null,
            folder: 'users',
        );

        $data = $this->moveData(
            id: 'request-1234',
            folder: '',
            name: null,
            hash: $stored->hash,
        );

        $action = resolve(MoveWorkspaceRequestAction::class);

        // Act

        $action->execute(data: $data);

        // Assert

        $this->assertFileExists("{$this->workspacePath}/requests/create-user.request.json");
    }

    public function test_it_renames_a_request_and_its_file(): void
    {
        // Arrange

        $stored = $this->saveRequest(
            $this->savedRequest(),
            hash: null,
            folder: 'users',
        );

        $data = $this->moveData(
            id: 'request-1234',
            folder: null,
            name: 'Register user',
            hash: $stored->hash,
        );

        $action = resolve(MoveWorkspaceRequestAction::class);

        // Act

        $moved = $action->execute(data: $data);

        // Assert

        $this->assertSame(
            'Register user',
            $moved->savedRequest->name,
        );

        $this->assertFileDoesNotExist("{$this->workspacePath}/requests/users/create-user.request.json");
        $this->assertFileExists("{$this->workspacePath}/requests/users/register-user.request.json");
    }

    public function test_it_picks_a_free_file_name_when_the_target_is_taken(): void
    {
        // Arrange

        $this->saveRequest(
            $this->savedRequest(id: 'request-0001'),
            hash: null,
            folder: 'users',
        );

        $stored = $this->saveRequest(
            $this->savedRequest(id: 'request-0002'),
            hash: null,
        );

        $data = $this->moveData(
            id: 'request-0002',
            folder: 'users',
            name: null,
            hash: $stored->hash,
        );

        $action = resolve(MoveWorkspaceRequestAction::class);

        // Act

        $action->execute(data: $data);

        // Assert

        $this->assertFileExists("{$this->workspacePath}/requests/users/create-user.request.json");
        $this->assertFileExists("{$this->workspacePath}/requests/users/create-user-2.request.json");
    }

    public function test_it_moves_a_request_in_the_manifest(): void
    {
        // Arrange

        $stored = $this->saveRequest(
            $this->savedRequest(),
            hash: null,
        );

        $this->createFolder('users');

        $data = $this->moveData(
            id: 'request-1234',
            folder: 'users',
            name: null,
            hash: $stored->hash,
        );

        $action = resolve(MoveWorkspaceRequestAction::class);

        // Act

        $action->execute(data: $data);

        // Assert

        $this->assertSame(
            ['users' => ['request-1234']],
            $this->readManifest()['requests'],
        );
    }

    public function test_it_keeps_the_position_of_a_request_that_is_only_renamed(): void
    {
        // Arrange

        $first = $this->saveRequest(
            $this->savedRequest(
                id: 'request-aaaa',
                name: 'First',
            ),
            hash: null,
        );

        $this->saveRequest(
            $this->savedRequest(
                id: 'request-bbbb',
                name: 'Second',
            ),
            hash: null,
        );

        $data = $this->moveData(
            id: 'request-aaaa',
            folder: null,
            name: 'Renamed',
            hash: $first->hash,
        );

        $action = resolve(MoveWorkspaceRequestAction::class);

        // Act

        $action->execute(data: $data);

        // Assert

        $this->assertSame(
            ['' => ['request-aaaa', 'request-bbbb']],
            $this->readManifest()['requests'],
        );
    }

    public function test_it_puts_a_request_that_changes_folder_last_in_the_new_folder(): void
    {
        // Arrange

        $first = $this->saveRequest(
            $this->savedRequest(
                id: 'request-aaaa',
                name: 'First',
            ),
            hash: null,
        );

        $this->saveRequest(
            $this->savedRequest(
                id: 'request-bbbb',
                name: 'Second',
            ),
            hash: null,
        );

        $this->saveRequest(
            $this->savedRequest(
                id: 'request-cccc',
                name: 'Third',
            ),
            hash: null,
        );

        $data = $this->moveData(
            id: 'request-aaaa',
            folder: 'other',
            name: null,
            hash: $first->hash,
        );

        $action = resolve(MoveWorkspaceRequestAction::class);

        // Act

        $action->execute(data: $data);

        // Assert

        $this->assertSame(
            ['' => ['request-bbbb', 'request-cccc'], 'other' => ['request-aaaa']],
            $this->readManifest()['requests'],
        );
    }

    public function test_it_refuses_to_move_a_file_changed_since_it_was_loaded(): void
    {
        // Arrange

        $this->saveRequest(
            $this->savedRequest(),
            hash: null,
        );

        $data = $this->moveData(
            id: 'request-1234',
            folder: 'users',
            name: null,
            hash: 'stale',
        );

        $action = resolve(MoveWorkspaceRequestAction::class);

        // Act

        try {
            $action->execute(data: $data);
        } catch (WorkspaceException $exception) {
        }

        // Assert

        $this->assertSame(
            409,
            $exception->status,
        );

        $this->assertFileExists("{$this->workspacePath}/requests/create-user.request.json");
    }

    public function test_it_fails_to_move_a_missing_request(): void
    {
        // Arrange

        $data = $this->moveData(
            id: 'request-9999',
            folder: 'users',
            name: null,
            hash: 'hash',
        );

        $action = resolve(MoveWorkspaceRequestAction::class);

        // Assert

        $this->expectException(WorkspaceException::class);
        $this->expectExceptionMessage('request-9999');

        // Act

        $action->execute(data: $data);
    }

    public function test_it_scans_only_after_taking_the_lock(): void
    {
        // Arrange

        $stored = $this->saveRequest(
            $this->savedRequest(),
            hash: null,
        );

        $scanSpy = $this->spyOnScanLockState();

        $data = $this->moveData(
            id: 'request-1234',
            folder: 'users',
            name: null,
            hash: $stored->hash,
        );

        $action = resolve(MoveWorkspaceRequestAction::class);

        // Act

        $action->execute(data: $data);

        // Assert

        $this->assertSame(
            [true],
            $scanSpy->lockedAtScan,
        );
    }

    private function moveData(string $id, ?string $folder, ?string $name, ?string $hash): MoveWorkspaceRequestData
    {
        $folderPath = $folder === null ? null : FolderPath::from($folder);

        return new MoveWorkspaceRequestData(
            id: $id,
            folder: $folderPath,
            name: $name,
            hash: $hash,
        );
    }
}
