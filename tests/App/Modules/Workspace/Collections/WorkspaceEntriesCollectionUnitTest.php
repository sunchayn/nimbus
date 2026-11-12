<?php

declare(strict_types=1);

namespace Sunchayn\Nimbus\Tests\App\Modules\Workspace\Collections;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Sunchayn\Nimbus\Modules\Workspace\Collections\WorkspaceEntriesCollection;
use Sunchayn\Nimbus\Modules\Workspace\DataTransferObjects\ManifestFile;
use Sunchayn\Nimbus\Modules\Workspace\DataTransferObjects\RequestFile;
use Sunchayn\Nimbus\Modules\Workspace\DataTransferObjects\SavedRequest;
use Sunchayn\Nimbus\Modules\Workspace\DataTransferObjects\WorkspaceEntry;
use Sunchayn\Nimbus\Modules\Workspace\Exceptions\WorkspaceException;
use Sunchayn\Nimbus\Modules\Workspace\ValueObjects\FolderPath;

#[CoversClass(WorkspaceEntriesCollection::class)]
class WorkspaceEntriesCollectionUnitTest extends TestCase
{
    public function test_it_puts_the_saved_entries_first_and_the_others_by_name(): void
    {
        // Arrange

        $entries = WorkspaceEntriesCollection::make([
            $this->folder('alpha'),
            $this->folder('bravo'),
            $this->folder('charlie'),
            $this->request(
                'one',
                'Zulu',
            ),
            $this->request(
                'two',
                'Yankee',
            ),
            $this->request(
                'three',
                'X-ray',
            ),
        ]);

        $manifest = new ManifestFile(
            folders: ['' => ['charlie', 'gone']],
            requests: ['' => ['two']],
        );

        // Act

        $ordered = $entries->sortByManifest($manifest);

        // Assert

        $this->assertSame(
            ['charlie', 'alpha', 'bravo'],
            $ordered->folderPaths()->map(strval(...))->all(),
        );

        $this->assertSame(
            ['two', 'three', 'one'],
            $ordered->requestFiles()->map(fn (RequestFile $file): string => $file->savedRequest->id)->all(),
        );
    }

    public function test_it_flattens_the_entries_by_hierarchy_with_the_requests_of_a_folder_first(): void
    {
        // Arrange

        $entries = WorkspaceEntriesCollection::make([
            $this->folder('users'),
            $this->folder('users/admin'),
            $this->folder('billing'),
            $this->request(
                'deep',
                'Deep',
                'users/admin',
            ),
            $this->request(
                'root',
                'Root',
            ),
            $this->request(
                'inusers',
                'In users',
                'users',
            ),
        ]);

        // Act

        $ordered = $entries->flattenedByHierarchy();

        // Assert

        $this->assertSame(
            ['root', 'users', 'inusers', 'users/admin', 'deep', 'billing'],
            $ordered->map(fn (WorkspaceEntry $entry): string => $entry->isFolder() ? (string) $entry->folderPath() : $entry->key)->all(),
        );
    }

    public function test_it_finds_a_request_by_its_id(): void
    {
        // Arrange

        $entries = WorkspaceEntriesCollection::make([
            $this->folder('users'),
            $this->request(
                'one',
                'One',
                'users',
            ),
        ]);

        // Act

        $found = $entries->findRequest('one');
        $missing = $entries->findRequest('missing');

        // Assert

        $this->assertSame(
            'One',
            $found?->savedRequest->name,
        );

        $this->assertNull($missing);
    }

    public function test_it_knows_which_folders_exist_and_the_root_always_does(): void
    {
        // Arrange

        $entries = WorkspaceEntriesCollection::make([$this->folder('users')]);

        // Act

        $hasRoot = $entries->hasFolder(FolderPath::root());
        $hasUsers = $entries->hasFolder(FolderPath::fromStored('users'));
        $hasMissing = $entries->hasFolder(FolderPath::fromStored('missing'));

        // Assert

        $this->assertTrue($hasRoot);
        $this->assertTrue($hasUsers);
        $this->assertFalse($hasMissing);
    }

    public function test_it_orders_the_wanted_folders_and_leaves_the_requests(): void
    {
        // Arrange

        $entries = WorkspaceEntriesCollection::make([
            $this->folder('alpha'),
            $this->folder('bravo'),
            $this->request(
                'one',
                'One',
            ),
        ]);

        // Act

        $ordered = $entries->withFoldersOrdered([FolderPath::fromStored('bravo')]);

        // Assert

        $this->assertSame(
            ['bravo', 'alpha'],
            $ordered->folderPaths()->map(strval(...))->all(),
        );

        $this->assertSame(
            ['one'],
            $ordered->requestFiles()->map(fn (RequestFile $file): string => $file->savedRequest->id)->all(),
        );
    }

    public function test_it_orders_the_wanted_requests_of_one_folder_and_ignores_the_others(): void
    {
        // Arrange

        $entries = WorkspaceEntriesCollection::make([
            $this->request(
                'one',
                'One',
                'users',
            ),
            $this->request(
                'two',
                'Two',
                'users',
            ),
            $this->request(
                'other',
                'Other',
            ),
        ]);

        // Act

        $ordered = $entries->withRequestsOrdered(
            FolderPath::fromStored('users'),
            ['two', 'other'],
        );

        // Assert

        $this->assertSame(
            ['two', 'one'],
            $ordered->requestIdsByFolder()['users'],
        );
    }

    public function test_it_gives_the_names_and_ids_the_manifest_stores(): void
    {
        // Arrange

        $entries = WorkspaceEntriesCollection::make([
            $this->folder('users'),
            $this->folder('users/admin'),
            $this->request(
                'one',
                'One',
                'users',
            ),
        ]);

        // Act

        $folderNames = $entries->folderNamesByParent();
        $requestIds = $entries->requestIdsByFolder();

        // Assert

        $this->assertSame(
            ['' => ['users'], 'users' => ['admin']],
            $folderNames,
        );

        $this->assertSame(
            ['users' => ['one']],
            $requestIds,
        );
    }

    public function test_it_fails_to_find_a_missing_request(): void
    {
        // Arrange

        $entries = WorkspaceEntriesCollection::make([$this->request(
            'one',
            'One',
        )]);

        // Assert

        $this->expectException(WorkspaceException::class);
        $this->expectExceptionMessage('request-9999');

        // Act

        $entries->findRequestOrFail('request-9999');
    }

    private function folder(string $path): WorkspaceEntry
    {
        return WorkspaceEntry::folder(FolderPath::fromStored($path));
    }

    private function request(string $id, string $name, string $folder = ''): WorkspaceEntry
    {
        $requestFile = new RequestFile(
            filepath: "/workspace/requests/{$folder}/{$id}.request.json",
            relativeFilepath: "requests/{$folder}/{$id}.request.json",
            folder: FolderPath::fromStored($folder),
            savedRequest: SavedRequest::fromArray(['id' => $id, 'name' => $name, 'method' => 'get']),
            hash: 'hash',
        );

        return WorkspaceEntry::request($requestFile);
    }
}
