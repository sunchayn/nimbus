<?php

declare(strict_types=1);

namespace Sunchayn\Nimbus\Tests\App\Modules\Workspace\Actions;

use PHPUnit\Framework\Attributes\CoversClass;
use Sunchayn\Nimbus\Modules\Workspace\Actions\DeleteWorkspaceFolderAction;
use Sunchayn\Nimbus\Modules\Workspace\Exceptions\WorkspaceException;
use Sunchayn\Nimbus\Modules\Workspace\ValueObjects\FolderPath;
use Sunchayn\Nimbus\Tests\Concerns\UsesTemporaryWorkspace;
use Sunchayn\Nimbus\Tests\TestCase;

#[CoversClass(DeleteWorkspaceFolderAction::class)]
class DeleteWorkspaceFolderActionFunctionalTest extends TestCase
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

    public function test_it_deletes_a_folder_with_its_content(): void
    {
        // Arrange

        $this->saveRequest(
            $this->savedRequest(),
            hash: null,
            folder: 'users/admin',
        );

        $this->createFolder('other');

        $folder = FolderPath::from('users');

        $action = resolve(DeleteWorkspaceFolderAction::class);

        // Act

        $action->execute(folder: $folder);

        // Assert

        $this->assertDirectoryDoesNotExist("{$this->workspacePath}/requests/users");
        $this->assertDirectoryExists("{$this->workspacePath}/requests/other");

        $this->assertSame(
            [],
            $this->listRequestIds(),
        );
    }

    public function test_it_forgets_the_order_of_a_deleted_folder(): void
    {
        // Arrange

        $this->createFolder('users/admin');
        $this->createFolder('other');

        $folder = FolderPath::from('users');

        $action = resolve(DeleteWorkspaceFolderAction::class);

        // Act

        $action->execute(folder: $folder);

        // Assert

        $this->assertSame(
            ['' => ['other']],
            $this->readManifest()['folders'],
        );
    }

    public function test_it_drops_a_deleted_folder_and_everything_under_it_from_the_manifest(): void
    {
        // Arrange

        $this->createFolder('users/admin');
        $this->createFolder('billing');

        $this->saveRequest(
            $this->savedRequest(),
            hash: null,
            folder: 'users/admin',
        );

        $folder = FolderPath::from('users');

        $action = resolve(DeleteWorkspaceFolderAction::class);

        // Act

        $action->execute(folder: $folder);

        // Assert

        $manifest = $this->readManifest();

        $this->assertSame(
            ['' => ['billing']],
            $manifest['folders'],
        );

        $this->assertSame(
            [],
            $manifest['requests'],
        );
    }

    public function test_it_refuses_a_folder_that_does_not_exist(): void
    {
        // Arrange

        $folder = FolderPath::from('missing');

        $action = resolve(DeleteWorkspaceFolderAction::class);

        // Assert

        $this->expectException(WorkspaceException::class);
        $this->expectExceptionMessage('does not exist in the workspace');

        // Act

        $action->execute(folder: $folder);
    }

    public function test_it_refuses_the_root_folder(): void
    {
        // Arrange

        $folder = FolderPath::root();

        $action = resolve(DeleteWorkspaceFolderAction::class);

        // Assert

        $this->expectException(WorkspaceException::class);
        $this->expectExceptionMessage('The folder name is empty');

        // Act

        $action->execute(folder: $folder);
    }

    public function test_it_does_not_follow_a_link_out_of_the_requests_folder(): void
    {
        // Arrange

        $outside = "{$this->workspacePath}/outside";

        mkdir($outside, 0755, true);

        file_put_contents("{$outside}/keep.txt", 'keep');

        $this->createFolder('users');

        symlink($outside, "{$this->workspacePath}/requests/users/link");

        $folder = FolderPath::from('users');

        $action = resolve(DeleteWorkspaceFolderAction::class);

        // Act

        $action->execute(folder: $folder);

        // Assert

        $this->assertDirectoryDoesNotExist("{$this->workspacePath}/requests/users");
        $this->assertFileExists("{$outside}/keep.txt");
    }

    public function test_it_scans_only_after_taking_the_lock(): void
    {
        // Arrange

        $this->createFolder('users');

        $scanSpy = $this->spyOnScanLockState();

        $folder = FolderPath::from('users');

        $action = resolve(DeleteWorkspaceFolderAction::class);

        // Act

        $action->execute(folder: $folder);

        // Assert

        $this->assertSame(
            [true],
            $scanSpy->lockedAtScan,
        );
    }
}
