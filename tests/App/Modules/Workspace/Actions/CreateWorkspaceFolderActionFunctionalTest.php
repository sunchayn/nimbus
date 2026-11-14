<?php

declare(strict_types=1);

namespace Sunchayn\Nimbus\Tests\App\Modules\Workspace\Actions;

use PHPUnit\Framework\Attributes\CoversClass;
use Sunchayn\Nimbus\Modules\Workspace\Actions\CreateWorkspaceFolderAction;
use Sunchayn\Nimbus\Modules\Workspace\Exceptions\WorkspaceException;
use Sunchayn\Nimbus\Modules\Workspace\ValueObjects\FolderPath;
use Sunchayn\Nimbus\Tests\Concerns\UsesTemporaryWorkspace;
use Sunchayn\Nimbus\Tests\TestCase;

#[CoversClass(CreateWorkspaceFolderAction::class)]
class CreateWorkspaceFolderActionFunctionalTest extends TestCase
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

    public function test_it_creates_nested_folders_from_slugs(): void
    {
        // Arrange

        $folder = FolderPath::from('Users / Admin Area');

        $action = resolve(CreateWorkspaceFolderAction::class);

        // Act

        $created = $action->execute(folder: $folder);

        // Assert

        $this->assertSame(
            'users/admin-area',
            (string) $created,
        );

        $this->assertDirectoryExists("{$this->workspacePath}/requests/users/admin-area");

        $this->assertSame(
            ['users', 'users/admin-area'],
            $this->listFolders(),
        );
    }

    public function test_it_puts_a_new_folder_at_the_end(): void
    {
        // Arrange

        mkdir("{$this->workspacePath}/requests/b", 0755, true);

        mkdir("{$this->workspacePath}/requests/c", 0755, true);

        $folder = FolderPath::from('a');

        $action = resolve(CreateWorkspaceFolderAction::class);

        // Act

        $action->execute(folder: $folder);

        // Assert

        $this->assertSame(
            ['b', 'c', 'a'],
            $this->listFolders(),
        );
    }

    public function test_it_adds_every_new_folder_of_a_path_to_the_manifest(): void
    {
        // Arrange

        $folder = FolderPath::from('users/admin');

        $action = resolve(CreateWorkspaceFolderAction::class);

        // Act

        $action->execute(folder: $folder);

        // Assert

        $this->assertSame(
            ['' => ['users'], 'users' => ['admin']],
            $this->readManifest()['folders'],
        );
    }

    public function test_it_keeps_the_folder_when_it_already_exists(): void
    {
        // Arrange

        $this->createFolder('users');

        $folder = FolderPath::from('users');

        $action = resolve(CreateWorkspaceFolderAction::class);

        // Act

        $created = $action->execute(folder: $folder);

        // Assert

        $this->assertSame(
            'users',
            (string) $created,
        );

        $this->assertSame(
            ['users'],
            $this->listFolders(),
        );

        $this->assertSame(
            ['' => ['users']],
            $this->readManifest()['folders'],
        );
    }

    public function test_it_cannot_leave_the_requests_folder_through_a_folder_name(): void
    {
        // Arrange

        $folder = FolderPath::from('../../escaped/./..\\up');

        $action = resolve(CreateWorkspaceFolderAction::class);

        // Act

        $created = $action->execute(folder: $folder);

        // Assert

        $this->assertSame(
            'escaped/up',
            (string) $created,
        );

        $this->assertDirectoryExists("{$this->workspacePath}/requests/escaped/up");
        $this->assertDirectoryDoesNotExist(base_path('escaped'));
    }

    public function test_it_refuses_the_root_folder(): void
    {
        // Arrange

        $folder = FolderPath::from('../..');

        $action = resolve(CreateWorkspaceFolderAction::class);

        // Assert

        $this->expectException(WorkspaceException::class);
        $this->expectExceptionMessage('The folder name is empty');

        // Act

        $action->execute(folder: $folder);
    }

    public function test_it_scans_only_after_taking_the_lock(): void
    {
        // Arrange

        $scanSpy = $this->spyOnScanLockState();

        $folder = FolderPath::from('users');

        $action = resolve(CreateWorkspaceFolderAction::class);

        // Act

        $action->execute(folder: $folder);

        // Assert

        $this->assertSame(
            [true],
            $scanSpy->lockedAtScan,
        );
    }
}
