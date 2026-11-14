<?php

declare(strict_types=1);

namespace Sunchayn\Nimbus\Tests\App\Modules\Workspace\ValueObjects;

use PHPUnit\Framework\Attributes\CoversClass;
use Sunchayn\Nimbus\Modules\Workspace\Exceptions\WorkspaceException;
use Sunchayn\Nimbus\Modules\Workspace\ValueObjects\FolderPath;
use Sunchayn\Nimbus\Modules\Workspace\ValueObjects\WorkspaceConfig;
use Sunchayn\Nimbus\Tests\Concerns\UsesTemporaryWorkspace;
use Sunchayn\Nimbus\Tests\TestCase;

#[CoversClass(WorkspaceConfig::class)]
#[CoversClass(WorkspaceException::class)]
class WorkspaceConfigFunctionalTest extends TestCase
{
    use UsesTemporaryWorkspace;

    protected function tearDown(): void
    {
        $this->tearDownWorkspace();

        parent::tearDown();
    }

    public function test_it_builds_the_paths_of_the_workspace_from_the_config(): void
    {
        // Arrange

        $this->enableWorkspace();

        $config = resolve(WorkspaceConfig::class);

        // Act & Assert

        $this->assertTrue($config->isEnabled());

        $this->assertSame(
            $this->workspacePath,
            $config->workspaceDirectory(),
        );

        $this->assertSame(
            $this->workspacePath.'/nimbus.json',
            $config->manifestPath(),
        );

        $this->assertSame(
            $this->workspacePath.'/requests',
            $config->requestsDirectory(),
        );

        $this->assertSame(
            $this->workspacePath.'/requests',
            $config->folderDirectory(FolderPath::root()),
        );

        $this->assertSame(
            $this->workspacePath.'/requests/users/admin',
            $config->folderDirectory(FolderPath::from('users/admin')),
        );
    }

    public function test_it_finds_the_folder_and_the_relative_path_of_a_file(): void
    {
        // Arrange

        $this->enableWorkspace();

        $config = resolve(WorkspaceConfig::class);

        $file = $this->workspacePath.'/requests/users/admin/create.request.json';

        // Act & Assert

        $this->assertSame(
            'users/admin',
            (string) $config->folderOf($file),
        );

        $this->assertTrue($config->folderOf($this->workspacePath.'/requests/create.request.json')->isRoot());

        $this->assertSame(
            'requests/users/admin/create.request.json',
            $config->relativeFilepath($file),
        );
    }

    public function test_it_refuses_the_root_when_disabled(): void
    {
        // Arrange

        config()->set(
            'nimbus.workspace.enabled',
            false,
        );

        $config = resolve(WorkspaceConfig::class);

        // Act & Assert

        $this->assertFalse($config->isEnabled());

        $this->expectException(WorkspaceException::class);

        $config->workspaceDirectory();
    }
}
