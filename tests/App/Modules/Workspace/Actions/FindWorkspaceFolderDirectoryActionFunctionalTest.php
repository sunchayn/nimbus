<?php

declare(strict_types=1);

namespace Sunchayn\Nimbus\Tests\App\Modules\Workspace\Actions;

use PHPUnit\Framework\Attributes\CoversClass;
use Sunchayn\Nimbus\Modules\Workspace\Actions\FindWorkspaceFolderDirectoryAction;
use Sunchayn\Nimbus\Modules\Workspace\Exceptions\WorkspaceException;
use Sunchayn\Nimbus\Modules\Workspace\ValueObjects\FolderPath;
use Sunchayn\Nimbus\Tests\Concerns\UsesTemporaryWorkspace;
use Sunchayn\Nimbus\Tests\TestCase;

#[CoversClass(FindWorkspaceFolderDirectoryAction::class)]
#[CoversClass(WorkspaceException::class)]
class FindWorkspaceFolderDirectoryActionFunctionalTest extends TestCase
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

    public function test_it_returns_the_directory_of_an_existing_folder(): void
    {
        // Arrange

        $this->createFolder('users/admin');

        $folder = FolderPath::from('users/admin');

        $action = resolve(FindWorkspaceFolderDirectoryAction::class);

        // Act

        $dirpath = $action->execute(folder: $folder);

        // Assert

        $this->assertSame(
            "{$this->workspacePath}/requests/users/admin",
            $dirpath,
        );
    }

    public function test_it_returns_the_requests_directory_for_the_root_folder(): void
    {
        // Arrange

        $this->createFolder('users');

        $folder = FolderPath::root();

        $action = resolve(FindWorkspaceFolderDirectoryAction::class);

        // Act

        $dirpath = $action->execute(folder: $folder);

        // Assert

        $this->assertSame(
            "{$this->workspacePath}/requests",
            $dirpath,
        );
    }

    public function test_it_rejects_a_folder_that_does_not_exist(): void
    {
        // Arrange

        $folder = FolderPath::from('missing');

        $action = resolve(FindWorkspaceFolderDirectoryAction::class);

        // Assert

        $this->expectException(WorkspaceException::class);
        $this->expectExceptionMessage('does not exist in the workspace');

        // Act

        $action->execute(folder: $folder);
    }

    public function test_it_rejects_a_link_to_a_folder_outside_the_requests_folder(): void
    {
        // Arrange

        $outside = "{$this->workspacePath}/outside";

        mkdir($outside, 0755, true);

        $this->createFolder('users');

        symlink($outside, "{$this->workspacePath}/requests/linked");

        $folder = FolderPath::from('linked');

        $action = resolve(FindWorkspaceFolderDirectoryAction::class);

        // Assert

        $this->expectException(WorkspaceException::class);
        $this->expectExceptionMessage('does not exist in the workspace');

        // Act

        $action->execute(folder: $folder);
    }

    public function test_it_rejects_a_link_to_another_folder_of_the_workspace(): void
    {
        // Arrange

        $this->createFolder('users');

        symlink("{$this->workspacePath}/requests/users", "{$this->workspacePath}/requests/alias");

        $folder = FolderPath::from('alias');

        $action = resolve(FindWorkspaceFolderDirectoryAction::class);

        // Assert

        $this->expectException(WorkspaceException::class);
        $this->expectExceptionMessage('does not exist in the workspace');

        // Act

        $action->execute(folder: $folder);
    }
}
