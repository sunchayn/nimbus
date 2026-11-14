<?php

declare(strict_types=1);

namespace Sunchayn\Nimbus\Tests\App\Modules\Workspace\Actions;

use PHPUnit\Framework\Attributes\CoversClass;
use Sunchayn\Nimbus\Modules\Workspace\Actions\SaveRequestFileAction;
use Sunchayn\Nimbus\Modules\Workspace\Actions\ScanWorkspaceAction;
use Sunchayn\Nimbus\Modules\Workspace\DataTransferObjects\ManifestFile;
use Sunchayn\Nimbus\Modules\Workspace\DataTransferObjects\RequestFile;
use Sunchayn\Nimbus\Modules\Workspace\DataTransferObjects\SavedRequest;
use Sunchayn\Nimbus\Modules\Workspace\DataTransferObjects\SaveRequestFileData;
use Sunchayn\Nimbus\Modules\Workspace\Exceptions\WorkspaceException;
use Sunchayn\Nimbus\Tests\Concerns\UsesTemporaryWorkspace;
use Sunchayn\Nimbus\Tests\TestCase;

#[CoversClass(SaveRequestFileAction::class)]
#[CoversClass(WorkspaceException::class)]
class SaveRequestFileActionFunctionalTest extends TestCase
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

    public function test_it_writes_a_new_file_and_adds_it_to_the_manifest(): void
    {
        // Arrange

        $filepath = "{$this->workspacePath}/requests/users/create-user.request.json";

        $data = $this->saveData(
            filepath: $filepath,
            previous: null,
            hash: null,
        );

        $action = resolve(SaveRequestFileAction::class);

        // Act

        $requestFile = $action->execute(data: $data);

        // Assert

        $this->assertFileExists($filepath);

        $this->assertSame(
            'users',
            (string) $requestFile->folder,
        );

        $this->assertSame(
            'requests/users/create-user.request.json',
            $requestFile->relativeFilepath,
        );

        $this->assertSame(
            hash_file('xxh3', $filepath),
            $requestFile->hash,
        );

        $this->assertSame(
            ['users' => ['request-1234']],
            $this->readManifest()['requests'],
        );
    }

    public function test_it_removes_the_previous_file_when_the_path_changes(): void
    {
        // Arrange

        $this->saveRequest(
            $this->savedRequest(),
            hash: null,
        );

        $previous = $this->findRequest('request-1234');

        $target = "{$this->workspacePath}/requests/renamed.request.json";

        $data = $this->saveData(
            filepath: $target,
            previous: $previous,
            hash: $previous->hash,
        );

        $action = resolve(SaveRequestFileAction::class);

        // Act

        $action->execute(data: $data);

        // Assert

        $this->assertFileExists($target);
        $this->assertFileDoesNotExist($previous->filepath);
    }

    public function test_it_keeps_the_file_when_the_path_is_the_same(): void
    {
        // Arrange

        $this->saveRequest(
            $this->savedRequest(),
            hash: null,
        );

        $previous = $this->findRequest('request-1234');

        $data = $this->saveData(
            filepath: $previous->filepath,
            previous: $previous,
            hash: $previous->hash,
            savedRequest: $this->savedRequest(name: 'Register user'),
        );

        $action = resolve(SaveRequestFileAction::class);

        // Act

        $action->execute(data: $data);

        // Assert

        $this->assertFileExists($previous->filepath);

        $this->assertSame(
            'Register user',
            $this->findRequest('request-1234')->savedRequest->name,
        );
    }

    public function test_it_keeps_the_position_in_the_manifest_when_the_folder_stays_the_same(): void
    {
        // Arrange

        $this->saveRequest(
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

        $previous = $this->findRequest('request-aaaa');

        $manifest = resolve(ScanWorkspaceAction::class)->execute()->manifest;

        $data = $this->saveData(
            filepath: "{$this->workspacePath}/requests/renamed.request.json",
            previous: $previous,
            hash: $previous->hash,
            savedRequest: $this->savedRequest(
                id: 'request-aaaa',
                name: 'Renamed',
            ),
            manifest: $manifest,
        );

        $action = resolve(SaveRequestFileAction::class);

        // Act

        $action->execute(data: $data);

        // Assert

        $this->assertSame(
            ['' => ['request-aaaa', 'request-bbbb']],
            $this->readManifest()['requests'],
        );
    }

    public function test_it_moves_the_request_to_the_end_of_the_new_folder_in_the_manifest(): void
    {
        // Arrange

        $this->saveRequest(
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
            folder: 'users',
        );

        $previous = $this->findRequest('request-aaaa');

        $manifest = resolve(ScanWorkspaceAction::class)->execute()->manifest;

        $data = $this->saveData(
            filepath: "{$this->workspacePath}/requests/users/first.request.json",
            previous: $previous,
            hash: $previous->hash,
            savedRequest: $this->savedRequest(
                id: 'request-aaaa',
                name: 'First',
            ),
            manifest: $manifest,
        );

        $action = resolve(SaveRequestFileAction::class);

        // Act

        $action->execute(data: $data);

        // Assert

        $this->assertSame(
            ['users' => ['request-bbbb', 'request-aaaa']],
            $this->readManifest()['requests'],
        );
    }

    public function test_it_refuses_a_stale_hash_and_leaves_the_files_alone(): void
    {
        // Arrange

        $this->saveRequest(
            $this->savedRequest(),
            hash: null,
        );

        $previous = $this->findRequest('request-1234');

        $target = "{$this->workspacePath}/requests/renamed.request.json";

        $data = $this->saveData(
            filepath: $target,
            previous: $previous,
            hash: 'stale',
        );

        $action = resolve(SaveRequestFileAction::class);

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

        $this->assertFileExists($previous->filepath);
        $this->assertFileDoesNotExist($target);
    }

    public function test_it_refuses_a_hash_when_there_is_no_previous_file(): void
    {
        // Arrange

        $filepath = "{$this->workspacePath}/requests/create-user.request.json";

        $data = $this->saveData(
            filepath: $filepath,
            previous: null,
            hash: 'some-hash',
        );

        $action = resolve(SaveRequestFileAction::class);

        // Assert

        $this->expectException(WorkspaceException::class);
        $this->expectExceptionMessage('changed on disk');

        // Act

        $action->execute(data: $data);
    }

    private function saveData(
        string $filepath,
        ?RequestFile $previous,
        ?string $hash,
        ?SavedRequest $savedRequest = null,
        ?ManifestFile $manifest = null,
    ): SaveRequestFileData {
        return new SaveRequestFileData(
            savedRequest: $savedRequest ?? $this->savedRequest(),
            filepath: $filepath,
            previous: $previous,
            hash: $hash,
            manifest: $manifest ?? new ManifestFile,
        );
    }
}
