<?php

declare(strict_types=1);

namespace Sunchayn\Nimbus\Tests\App\Modules\Workspace\Actions;

use Illuminate\Support\Facades\File;
use PHPUnit\Framework\Attributes\CoversClass;
use Stringable;
use Sunchayn\Nimbus\Modules\Workspace\Actions\WriteWorkspaceFileAction;
use Sunchayn\Nimbus\Modules\Workspace\DataTransferObjects\ManifestFile;
use Sunchayn\Nimbus\Modules\Workspace\DataTransferObjects\SavedRequest;
use Sunchayn\Nimbus\Modules\Workspace\DataTransferObjects\WriteWorkspaceFileData;
use Sunchayn\Nimbus\Modules\Workspace\Exceptions\WorkspaceException;
use Sunchayn\Nimbus\Tests\Concerns\UsesTemporaryWorkspace;
use Sunchayn\Nimbus\Tests\TestCase;

#[CoversClass(WriteWorkspaceFileAction::class)]
#[CoversClass(WorkspaceException::class)]
class WriteWorkspaceFileActionFunctionalTest extends TestCase
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

    public function test_it_writes_the_contents_and_returns_their_hash(): void
    {
        // Arrange

        $manifest = new ManifestFile(folders: ['' => ['users']]);

        $filepath = "{$this->workspacePath}/nimbus.json";

        $data = new WriteWorkspaceFileData(
            filepath: $filepath,
            contents: $manifest,
        );

        $action = resolve(WriteWorkspaceFileAction::class);

        // Act

        $hash = $action->execute(data: $data);

        // Assert

        $this->assertSame(
            (string) $manifest,
            file_get_contents($filepath),
        );

        $this->assertSame(
            hash('xxh3', (string) $manifest),
            $hash,
        );

        $this->assertSame(
            hash_file('xxh3', $filepath),
            $hash,
        );
    }

    public function test_it_creates_the_missing_directories(): void
    {
        // Arrange

        $filepath = "{$this->workspacePath}/requests/users/admin/create.request.json";

        $data = new WriteWorkspaceFileData(
            filepath: $filepath,
            contents: new ManifestFile,
        );

        $action = resolve(WriteWorkspaceFileAction::class);

        // Act

        $action->execute(data: $data);

        // Assert

        $this->assertFileExists($filepath);
    }

    public function test_it_replaces_the_content_of_an_existing_file(): void
    {
        // Arrange

        File::ensureDirectoryExists($this->workspacePath);

        $filepath = "{$this->workspacePath}/nimbus.json";

        file_put_contents($filepath, 'old');

        $manifest = new ManifestFile(folders: ['' => ['users']]);

        $data = new WriteWorkspaceFileData(
            filepath: $filepath,
            contents: $manifest,
        );

        $action = resolve(WriteWorkspaceFileAction::class);

        // Act

        $action->execute(data: $data);

        // Assert

        $this->assertSame(
            (string) $manifest,
            file_get_contents($filepath),
        );
    }

    public function test_it_leaves_no_temporary_file_behind(): void
    {
        // Arrange

        $filepath = "{$this->workspacePath}/nimbus.json";

        $data = new WriteWorkspaceFileData(
            filepath: $filepath,
            contents: new ManifestFile,
        );

        $action = resolve(WriteWorkspaceFileAction::class);

        // Act

        $action->execute(data: $data);

        // Assert

        $this->assertSame(
            ['nimbus.json'],
            array_map(
                basename(...),
                File::files(
                    $this->workspacePath,
                    hidden: true,
                ),
            ),
        );
    }

    public function test_it_refuses_contents_larger_than_one_megabyte(): void
    {
        // Arrange

        $filepath = "{$this->workspacePath}/nimbus.json";

        $contents = new class implements Stringable
        {
            public function __toString(): string
            {
                return str_repeat('a', SavedRequest::MAX_FILE_SIZE_IN_BYTES + 1);
            }
        };

        $data = new WriteWorkspaceFileData(
            filepath: $filepath,
            contents: $contents,
        );

        $action = resolve(WriteWorkspaceFileAction::class);

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

        $this->assertFileDoesNotExist($filepath);
    }

    public function test_it_fails_and_removes_the_temporary_file_when_the_target_cannot_be_replaced(): void
    {
        // Arrange

        $filepath = "{$this->workspacePath}/nimbus.json";

        File::ensureDirectoryExists($filepath);

        $data = new WriteWorkspaceFileData(
            filepath: $filepath,
            contents: new ManifestFile,
        );

        $action = resolve(WriteWorkspaceFileAction::class);

        // Act

        try {
            $action->execute(data: $data);
        } catch (WorkspaceException $exception) {
        }

        // Assert

        $this->assertSame(
            500,
            $exception->status,
        );

        $this->assertSame(
            [],
            File::files(
                $this->workspacePath,
                hidden: true,
            ),
        );
    }
}
