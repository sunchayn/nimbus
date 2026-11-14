<?php

declare(strict_types=1);

namespace Sunchayn\Nimbus\Tests\App\Modules\Workspace\Actions;

use PHPUnit\Framework\Attributes\CoversClass;
use Sunchayn\Nimbus\Modules\Workspace\Actions\SaveWorkspaceRequestAction;
use Sunchayn\Nimbus\Modules\Workspace\DataTransferObjects\SavedRequest;
use Sunchayn\Nimbus\Modules\Workspace\DataTransferObjects\SaveWorkspaceRequestData;
use Sunchayn\Nimbus\Modules\Workspace\Exceptions\WorkspaceException;
use Sunchayn\Nimbus\Modules\Workspace\ValueObjects\FolderPath;
use Sunchayn\Nimbus\Tests\Concerns\UsesTemporaryWorkspace;
use Sunchayn\Nimbus\Tests\TestCase;

#[CoversClass(SaveWorkspaceRequestAction::class)]
#[CoversClass(WorkspaceException::class)]
class SaveWorkspaceRequestActionFunctionalTest extends TestCase
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

    public function test_it_creates_the_request_file_on_the_first_save(): void
    {
        // Arrange

        $data = $this->saveData(
            savedRequest: $this->savedRequest(),
            hash: null,
        );

        $action = resolve(SaveWorkspaceRequestAction::class);

        // Act

        $requestFile = $action->execute(data: $data);

        // Assert

        $this->assertFileExists("{$this->workspacePath}/requests/create-user.request.json");

        $this->assertSame(
            ['folders' => [], 'requests' => ['' => ['request-1234']]],
            $this->readManifest(),
        );

        $this->assertSame(
            'request-1234',
            $requestFile->savedRequest->id,
        );
    }

    public function test_it_writes_two_space_indented_files_with_a_trailing_new_line(): void
    {
        // Arrange

        $data = $this->saveData(
            savedRequest: $this->savedRequest(),
            hash: null,
        );

        $action = resolve(SaveWorkspaceRequestAction::class);

        // Act

        $action->execute(data: $data);

        // Assert

        $contents = (string) file_get_contents("{$this->workspacePath}/requests/create-user.request.json");

        $this->assertStringContainsString(
            "\n  \"id\": \"request-1234\",\n",
            $contents,
        );

        $this->assertStringContainsString(
            "\n    {\n      \"key\": \"Accept\",",
            $contents,
        );

        $this->assertStringEndsWith(
            "}\n",
            $contents,
        );
    }

    public function test_it_updates_the_file_without_renaming_it(): void
    {
        // Arrange

        $saved = $this->saveRequest(
            $this->savedRequest(),
            hash: null,
        );

        $data = $this->saveData(
            savedRequest: $this->savedRequest(name: 'Register user'),
            hash: $saved->hash,
        );

        $action = resolve(SaveWorkspaceRequestAction::class);

        // Act

        $updated = $action->execute(data: $data);

        // Assert

        $this->assertNotSame(
            $saved->hash,
            $updated->hash,
        );

        $this->assertFileExists("{$this->workspacePath}/requests/create-user.request.json");

        $this->assertSame(
            'Register user',
            $this->findRequest('request-1234')->savedRequest->name,
        );
    }

    public function test_it_gives_a_new_file_name_to_a_request_with_the_same_name(): void
    {
        // Arrange

        $this->saveRequest(
            $this->savedRequest(),
            hash: null,
        );

        $data = $this->saveData(
            savedRequest: $this->savedRequest(id: 'request-5678'),
            hash: null,
        );

        $action = resolve(SaveWorkspaceRequestAction::class);

        // Act

        $action->execute(data: $data);

        // Assert

        $this->assertFileExists("{$this->workspacePath}/requests/create-user-2.request.json");
    }

    public function test_it_saves_a_new_request_in_a_folder(): void
    {
        // Arrange

        $data = $this->saveData(
            savedRequest: $this->savedRequest(),
            hash: null,
            folder: 'Users',
        );

        $action = resolve(SaveWorkspaceRequestAction::class);

        // Act

        $requestFile = $action->execute(data: $data);

        // Assert

        $this->assertFileExists("{$this->workspacePath}/requests/users/create-user.request.json");

        $this->assertSame(
            'users',
            (string) $requestFile->folder,
        );

        $this->assertSame(
            'requests/users/create-user.request.json',
            $requestFile->relativeFilepath,
        );

        $this->assertNotSame(
            '',
            $requestFile->hash,
        );
    }

    public function test_it_keeps_an_existing_request_in_its_folder_on_save(): void
    {
        // Arrange

        $stored = $this->saveRequest(
            $this->savedRequest(),
            hash: null,
            folder: 'users',
        );

        $data = $this->saveData(
            savedRequest: $this->savedRequest(),
            hash: $stored->hash,
            folder: 'elsewhere',
        );

        $action = resolve(SaveWorkspaceRequestAction::class);

        // Act

        $action->execute(data: $data);

        // Assert

        $this->assertFileExists("{$this->workspacePath}/requests/users/create-user.request.json");
        $this->assertFileDoesNotExist("{$this->workspacePath}/requests/elsewhere/create-user.request.json");
    }

    public function test_it_lists_new_requests_at_the_end_of_their_folder(): void
    {
        // Arrange

        $this->saveRequest(
            $this->savedRequest(
                'b',
                'Beta',
            ),
            hash: null,
        );

        $data = $this->saveData(
            savedRequest: $this->savedRequest(
                'a',
                'Alpha',
            ),
            hash: null,
        );

        $action = resolve(SaveWorkspaceRequestAction::class);

        // Act

        $action->execute(data: $data);

        // Assert

        $this->assertSame(
            ['b', 'a'],
            $this->listRequestIds(),
        );

        $this->assertSame(
            ['' => ['b', 'a']],
            $this->readManifest()['requests'],
        );
    }

    public function test_it_waits_for_another_process_and_then_writes_on_the_fresh_state(): void
    {
        // Arrange

        $process = $this->holdTheLockInAnotherProcess(milliseconds: 600);

        $data = $this->saveData(
            savedRequest: $this->savedRequest(),
            hash: null,
        );

        $action = resolve(SaveWorkspaceRequestAction::class);

        $started = hrtime(true);

        // Act

        $requestFile = $action->execute(data: $data);

        // Assert

        $waited = (hrtime(true) - $started) / 1_000_000;

        $this->assertGreaterThanOrEqual(
            300,
            $waited,
        );

        $this->assertSame(
            'request-1234',
            $requestFile->savedRequest->id,
        );

        $this->assertSame(
            'request-1234',
            $this->findRequest('request-1234')->savedRequest->id,
        );

        $this->closeLockProcess($process);
    }

    public function test_it_refuses_to_overwrite_a_file_changed_since_it_was_loaded(): void
    {
        // Arrange

        $this->saveRequest(
            $this->savedRequest(),
            hash: null,
        );

        $data = $this->saveData(
            savedRequest: $this->savedRequest(name: 'Other'),
            hash: 'stale',
        );

        $action = resolve(SaveWorkspaceRequestAction::class);

        // Assert

        $this->expectException(WorkspaceException::class);
        $this->expectExceptionMessage('changed on disk');

        // Act

        $action->execute(data: $data);
    }

    public function test_it_refuses_to_overwrite_an_existing_file_without_a_hash(): void
    {
        // Arrange

        $this->saveRequest(
            $this->savedRequest(),
            hash: null,
        );

        $data = $this->saveData(
            savedRequest: $this->savedRequest(name: 'Other'),
            hash: null,
        );

        $action = resolve(SaveWorkspaceRequestAction::class);

        // Assert

        $this->expectException(WorkspaceException::class);
        $this->expectExceptionMessage('changed on disk');

        // Act

        $action->execute(data: $data);
    }

    public function test_it_refuses_a_hash_for_a_file_that_does_not_exist(): void
    {
        // Arrange

        $data = $this->saveData(
            savedRequest: $this->savedRequest(),
            hash: 'some-hash',
        );

        $action = resolve(SaveWorkspaceRequestAction::class);

        // Assert

        $this->expectException(WorkspaceException::class);
        $this->expectExceptionMessage('changed on disk');

        // Act

        $action->execute(data: $data);
    }

    public function test_it_refuses_a_file_larger_than_one_megabyte(): void
    {
        // Arrange

        $savedRequest = $this->savedRequest(bodyContent: str_repeat('a', 1_100_000));

        $data = $this->saveData(
            savedRequest: $savedRequest,
            hash: null,
        );

        $action = resolve(SaveWorkspaceRequestAction::class);

        // Act

        try {
            $action->execute(data: $data);
        } catch (WorkspaceException $exception) {
        }

        // Assert

        $this->assertSame(
            413,
            $exception->status,
        );

        $this->assertFileDoesNotExist("{$this->workspacePath}/requests/create-user.request.json");
    }

    public function test_it_scans_only_after_taking_the_lock(): void
    {
        // Arrange

        $scanSpy = $this->spyOnScanLockState();

        $data = $this->saveData(
            savedRequest: $this->savedRequest(),
            hash: null,
        );

        $action = resolve(SaveWorkspaceRequestAction::class);

        // Act

        $action->execute(data: $data);

        // Assert

        $this->assertSame(
            [true],
            $scanSpy->lockedAtScan,
        );
    }

    private function saveData(SavedRequest $savedRequest, ?string $hash, ?string $folder = null): SaveWorkspaceRequestData
    {
        return new SaveWorkspaceRequestData(
            savedRequest: $savedRequest,
            hash: $hash,
            folder: FolderPath::from($folder),
        );
    }
}
