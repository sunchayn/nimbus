<?php

declare(strict_types=1);

namespace Sunchayn\Nimbus\Tests\App\Modules\Workspace\DataTransferObjects;

use InvalidArgumentException;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Sunchayn\Nimbus\Modules\Workspace\DataTransferObjects\RequestFile;
use Sunchayn\Nimbus\Modules\Workspace\DataTransferObjects\SavedRequest;
use Sunchayn\Nimbus\Modules\Workspace\DataTransferObjects\WorkspaceEntry;
use Sunchayn\Nimbus\Modules\Workspace\Enums\EntryKind;
use Sunchayn\Nimbus\Modules\Workspace\ValueObjects\FolderPath;

#[CoversClass(WorkspaceEntry::class)]
class WorkspaceEntryUnitTest extends TestCase
{
    public function test_it_describes_a_folder_by_its_parent_and_name(): void
    {
        // Act

        $entry = WorkspaceEntry::folder(FolderPath::fromStored('users/admin'));

        // Assert

        $this->assertTrue($entry->isFolder());

        $this->assertSame(
            'users',
            (string) $entry->parent,
        );

        $this->assertSame(
            'admin',
            $entry->key,
        );

        $this->assertSame(
            'users/admin',
            (string) $entry->folderPath(),
        );

        $this->assertNull($entry->requestFile);
    }

    public function test_it_describes_a_request_by_its_folder_and_id(): void
    {
        // Arrange

        $requestFile = new RequestFile(
            filepath: '/workspace/requests/users/create.request.json',
            relativeFilepath: 'requests/users/create.request.json',
            folder: FolderPath::fromStored('users'),
            savedRequest: SavedRequest::fromArray(['id' => 'one', 'name' => 'Create', 'method' => 'post']),
            hash: 'hash',
        );

        // Act

        $entry = WorkspaceEntry::request($requestFile);

        // Assert

        $this->assertTrue($entry->isRequest());

        $this->assertSame(
            'users',
            (string) $entry->parent,
        );

        $this->assertSame(
            'one',
            $entry->key,
        );

        $this->assertSame(
            $requestFile,
            $entry->requestFile,
        );
    }

    public function test_it_tells_a_folder_and_a_request_with_the_same_key_apart(): void
    {
        // Arrange

        $requestFile = new RequestFile(
            filepath: '/workspace/requests/one.request.json',
            relativeFilepath: 'requests/one.request.json',
            folder: FolderPath::root(),
            savedRequest: SavedRequest::fromArray(['id' => 'one', 'name' => 'One', 'method' => 'get']),
            hash: 'hash',
        );

        $folder = WorkspaceEntry::folder(FolderPath::fromStored('one'));
        $request = WorkspaceEntry::request($requestFile);

        // Act

        $folderIdentity = $folder->identity();
        $requestIdentity = $request->identity();

        // Assert

        $this->assertSame(
            'folder||one',
            (string) $folderIdentity,
        );

        $this->assertSame(
            'request||one',
            (string) $requestIdentity,
        );
    }

    public function test_it_refuses_a_folder_with_a_request_file(): void
    {
        // Arrange

        $requestFile = new RequestFile(
            filepath: '/workspace/requests/create.request.json',
            relativeFilepath: 'requests/create.request.json',
            folder: FolderPath::root(),
            savedRequest: SavedRequest::fromArray(['id' => 'one', 'name' => 'Create', 'method' => 'post']),
            hash: 'hash',
        );

        // Assert

        $this->expectException(InvalidArgumentException::class);

        // Act

        new WorkspaceEntry(
            kind: EntryKind::Folder,
            parent: FolderPath::root(),
            key: 'users',
            requestFile: $requestFile,
        );
    }

    public function test_it_refuses_a_request_without_a_request_file(): void
    {
        // Assert

        $this->expectException(InvalidArgumentException::class);

        // Act

        new WorkspaceEntry(
            kind: EntryKind::Request,
            parent: FolderPath::root(),
            key: 'one',
        );
    }
}
