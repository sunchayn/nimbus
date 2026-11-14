<?php

declare(strict_types=1);

namespace Sunchayn\Nimbus\Tests\App\Modules\Workspace\Actions;

use PHPUnit\Framework\Attributes\CoversClass;
use Sunchayn\Nimbus\Modules\Workspace\Actions\ReorderWorkspaceFoldersAction;
use Sunchayn\Nimbus\Modules\Workspace\ValueObjects\FolderPath;
use Sunchayn\Nimbus\Tests\Concerns\UsesTemporaryWorkspace;
use Sunchayn\Nimbus\Tests\TestCase;

#[CoversClass(ReorderWorkspaceFoldersAction::class)]
class ReorderWorkspaceFoldersActionFunctionalTest extends TestCase
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

    public function test_it_reorders_folders_and_ignores_the_unknown_ones(): void
    {
        // Arrange

        $this->createFolder('one');
        $this->createFolder('two');
        $this->createFolder('three');

        $folders = $this->folderPaths(['three', 'unknown', 'one']);

        $action = resolve(ReorderWorkspaceFoldersAction::class);

        // Act

        $action->execute(folders: $folders);

        // Assert

        $this->assertSame(
            ['three', 'one', 'two'],
            $this->listFolders(),
        );

        $this->assertSame(
            ['' => ['three', 'one', 'two']],
            $this->readManifest()['folders'],
        );
    }

    public function test_it_reorders_the_folders_of_a_parent_and_keeps_their_content(): void
    {
        // Arrange

        $this->createFolder('users/alpha');
        $this->createFolder('users/bravo');

        $this->saveRequest(
            $this->savedRequest(),
            hash: null,
            folder: 'users/alpha',
        );

        $folders = $this->folderPaths(['users/bravo', 'users/alpha']);

        $action = resolve(ReorderWorkspaceFoldersAction::class);

        // Act

        $action->execute(folders: $folders);

        // Assert

        $manifest = $this->readManifest();

        $this->assertSame(
            ['' => ['users'], 'users' => ['bravo', 'alpha']],
            $manifest['folders'],
        );

        $this->assertSame(
            ['users/alpha' => ['request-1234']],
            $manifest['requests'],
        );
    }

    public function test_it_scans_only_after_taking_the_lock(): void
    {
        // Arrange

        $this->createFolder('users');
        $this->createFolder('billing');

        $scanSpy = $this->spyOnScanLockState();

        $folders = $this->folderPaths(['billing', 'users']);

        $action = resolve(ReorderWorkspaceFoldersAction::class);

        // Act

        $action->execute(folders: $folders);

        // Assert

        $this->assertSame(
            [true],
            $scanSpy->lockedAtScan,
        );
    }

    /**
     * @param  list<string>  $folders
     * @return list<FolderPath>
     */
    private function folderPaths(array $folders): array
    {
        return array_map(FolderPath::from(...), $folders);
    }
}
