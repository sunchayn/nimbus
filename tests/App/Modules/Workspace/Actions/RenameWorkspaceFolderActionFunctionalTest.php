<?php

declare(strict_types=1);

namespace Sunchayn\Nimbus\Tests\App\Modules\Workspace\Actions;

use PHPUnit\Framework\Attributes\CoversClass;
use Sunchayn\Nimbus\Modules\Workspace\Actions\RenameWorkspaceFolderAction;
use Sunchayn\Nimbus\Modules\Workspace\Exceptions\WorkspaceException;
use Sunchayn\Nimbus\Modules\Workspace\ValueObjects\FolderPath;
use Sunchayn\Nimbus\Tests\Concerns\UsesTemporaryWorkspace;
use Sunchayn\Nimbus\Tests\TestCase;

#[CoversClass(RenameWorkspaceFolderAction::class)]
#[CoversClass(WorkspaceException::class)]
class RenameWorkspaceFolderActionFunctionalTest extends TestCase
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

    public function test_it_renames_a_folder_and_keeps_its_place_and_requests(): void
    {
        // Arrange

        $this->createFolder('first');
        $this->createFolder('users/admin');
        $this->createFolder('last');

        $saved = $this->saveRequest(
            $this->savedRequest(),
            hash: null,
            folder: 'users/admin',
        );

        $data = $this->renameArguments(
            folder: 'users',
            name: 'People',
        );

        $action = resolve(RenameWorkspaceFolderAction::class);

        // Act

        $renamed = $action->execute(...$data);

        // Assert

        $this->assertSame(
            'people',
            (string) $renamed,
        );

        $this->assertSame(
            ['first', 'people', 'people/admin', 'last'],
            $this->listFolders(),
        );

        $this->assertDirectoryDoesNotExist("{$this->workspacePath}/requests/users");

        $this->assertSame(
            'people/admin',
            (string) $this->findRequest('request-1234')->folder,
        );

        $this->assertSame(
            $saved->hash,
            $this->findRequest('request-1234')->hash,
        );
    }

    public function test_it_rewrites_the_manifest_keys_and_keeps_the_position(): void
    {
        // Arrange

        $this->createFolder('first');
        $this->createFolder('users/admin');
        $this->createFolder('last');

        $this->saveRequest(
            $this->savedRequest(),
            hash: null,
            folder: 'users/admin',
        );

        $data = $this->renameArguments(
            folder: 'users',
            name: 'People',
        );

        $action = resolve(RenameWorkspaceFolderAction::class);

        // Act

        $action->execute(...$data);

        // Assert

        $manifest = $this->readManifest();

        $this->assertSame(
            ['' => ['first', 'people', 'last'], 'people' => ['admin']],
            $manifest['folders'],
        );

        $this->assertSame(
            ['people/admin' => ['request-1234']],
            $manifest['requests'],
        );
    }

    public function test_it_rewrites_the_manifest_of_a_nested_folder_and_keeps_its_place(): void
    {
        // Arrange

        $this->createFolder('users/admin');
        $this->createFolder('users/billing');
        $this->createFolder('users/zed');

        $this->saveRequest(
            $this->savedRequest(),
            hash: null,
            folder: 'users/billing',
        );

        $data = $this->renameArguments(
            folder: 'users/billing',
            name: 'finance',
        );

        $action = resolve(RenameWorkspaceFolderAction::class);

        // Act

        $action->execute(...$data);

        // Assert

        $manifest = $this->readManifest();

        $this->assertSame(
            ['' => ['users'], 'users' => ['admin', 'finance', 'zed']],
            $manifest['folders'],
        );

        $this->assertSame(
            ['users/finance' => ['request-1234']],
            $manifest['requests'],
        );
    }

    public function test_it_renames_a_nested_folder(): void
    {
        // Arrange

        $this->createFolder('users/admin');

        $data = $this->renameArguments(
            folder: 'users/admin',
            name: 'staff',
        );

        $action = resolve(RenameWorkspaceFolderAction::class);

        // Act

        $renamed = $action->execute(...$data);

        // Assert

        $this->assertSame(
            'users/staff',
            (string) $renamed,
        );

        $this->assertSame(
            ['users', 'users/staff'],
            $this->listFolders(),
        );
    }

    public function test_it_does_nothing_when_the_name_is_the_same(): void
    {
        // Arrange

        $this->createFolder('users');

        $data = $this->renameArguments(
            folder: 'users',
            name: 'Users',
        );

        $action = resolve(RenameWorkspaceFolderAction::class);

        // Act

        $renamed = $action->execute(...$data);

        // Assert

        $this->assertSame(
            'users',
            (string) $renamed,
        );

        $this->assertSame(
            ['users'],
            $this->listFolders(),
        );
    }

    public function test_it_refuses_a_name_that_is_taken(): void
    {
        // Arrange

        $this->createFolder('users');
        $this->createFolder('people');

        $data = $this->renameArguments(
            folder: 'users',
            name: 'people',
        );

        $action = resolve(RenameWorkspaceFolderAction::class);

        // Assert

        $this->expectException(WorkspaceException::class);
        $this->expectExceptionMessage('already exists');

        // Act

        $action->execute(...$data);
    }

    public function test_it_refuses_an_empty_name(): void
    {
        // Arrange

        $this->createFolder('users');

        $data = $this->renameArguments(
            folder: 'users',
            name: '!!!',
        );

        $action = resolve(RenameWorkspaceFolderAction::class);

        // Assert

        $this->expectException(WorkspaceException::class);
        $this->expectExceptionMessage('The folder name is empty');

        // Act

        $action->execute(...$data);
    }

    public function test_it_refuses_a_name_with_several_segments(): void
    {
        // Arrange

        $this->createFolder('users');

        $data = $this->renameArguments(
            folder: 'users',
            name: 'people/admin',
        );

        $action = resolve(RenameWorkspaceFolderAction::class);

        // Assert

        $this->expectException(WorkspaceException::class);
        $this->expectExceptionMessage('The folder name is empty');

        // Act

        $action->execute(...$data);
    }

    public function test_it_refuses_the_root_folder(): void
    {
        // Arrange

        $data = $this->renameArguments(
            folder: '',
            name: 'people',
        );

        $action = resolve(RenameWorkspaceFolderAction::class);

        // Assert

        $this->expectException(WorkspaceException::class);
        $this->expectExceptionMessage('The folder name is empty');

        // Act

        $action->execute(...$data);
    }

    public function test_it_refuses_a_folder_that_does_not_exist(): void
    {
        // Arrange

        $data = $this->renameArguments(
            folder: 'missing',
            name: 'other',
        );

        $action = resolve(RenameWorkspaceFolderAction::class);

        // Assert

        $this->expectException(WorkspaceException::class);
        $this->expectExceptionMessage('does not exist in the workspace');

        // Act

        $action->execute(...$data);
    }

    public function test_it_scans_only_after_taking_the_lock(): void
    {
        // Arrange

        $this->createFolder('users');

        $scanSpy = $this->spyOnScanLockState();

        $data = $this->renameArguments(
            folder: 'users',
            name: 'people',
        );

        $action = resolve(RenameWorkspaceFolderAction::class);

        // Act

        $action->execute(...$data);

        // Assert

        $this->assertSame(
            [true],
            $scanSpy->lockedAtScan,
        );
    }

    private function renameArguments(string $folder, string $name): array
    {
        return [
            'folder' => FolderPath::from($folder),
            'newName' => FolderPath::from($name),
        ];
    }
}
