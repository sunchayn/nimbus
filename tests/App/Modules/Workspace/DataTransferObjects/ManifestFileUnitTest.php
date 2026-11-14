<?php

declare(strict_types=1);

namespace Sunchayn\Nimbus\Tests\App\Modules\Workspace\DataTransferObjects;

use Generator;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Sunchayn\Nimbus\Modules\Workspace\Collections\WorkspaceEntriesCollection;
use Sunchayn\Nimbus\Modules\Workspace\DataTransferObjects\ManifestFile;
use Sunchayn\Nimbus\Modules\Workspace\DataTransferObjects\RequestFile;
use Sunchayn\Nimbus\Modules\Workspace\DataTransferObjects\SavedRequest;
use Sunchayn\Nimbus\Modules\Workspace\DataTransferObjects\WorkspaceEntry;
use Sunchayn\Nimbus\Modules\Workspace\ValueObjects\FolderPath;

#[CoversClass(ManifestFile::class)]
class ManifestFileUnitTest extends TestCase
{
    public function test_it_reads_the_two_maps_of_a_file(): void
    {
        // Arrange

        $json = '{"folders": {"": ["users", "billing"], "users": ["admin"]}, "requests": {"": ["one"], "users": ["two", "three"]}}';

        // Act

        $manifest = ManifestFile::fromRaw($json);

        // Assert

        $this->assertSame(
            ['' => ['users', 'billing'], 'users' => ['admin']],
            $manifest->folders,
        );

        $this->assertSame(
            ['' => ['one'], 'users' => ['two', 'three']],
            $manifest->requests,
        );
    }

    public function test_it_skips_malformed_entries(): void
    {
        // Arrange

        $json = '{"folders": {"users": "text", "valid": ["one", 12, "", null, "two", "one"], "empty": []}, "requests": ["one"]}';

        // Act

        $manifest = ManifestFile::fromRaw($json);

        // Assert

        $this->assertSame(
            ['valid' => ['one', 'two']],
            $manifest->folders,
        );

        $this->assertSame(
            [],
            $manifest->requests,
        );
    }

    #[DataProvider('unreadableContents')]
    public function test_it_reads_a_missing_or_invalid_file_as_empty(?string $json): void
    {
        // Act

        $manifest = ManifestFile::fromRaw($json);

        // Assert

        $this->assertSame(
            ['folders' => [], 'requests' => []],
            $manifest->toArray(),
        );
    }

    /**
     * @return Generator<string, array{json: string|null}>
     */
    public static function unreadableContents(): Generator
    {
        yield 'no file' => [
            'json' => null,
        ];

        yield 'invalid json' => [
            'json' => 'not json',
        ];

        yield 'a json text' => [
            'json' => '"text"',
        ];
    }

    public function test_it_keeps_numeric_folder_names(): void
    {
        // Arrange

        $manifest = ManifestFile::fromRaw('{"folders": {"": ["2024", "0"], "2024": ["12"]}, "requests": {"2024": ["one"], "0": ["two"]}}');

        // Act

        $reread = ManifestFile::fromRaw($manifest->toJson());

        // Assert

        $this->assertSame(
            ['' => ['2024', '0'], '2024' => ['12']],
            $reread->folders,
        );

        $this->assertSame(
            ['2024' => ['one'], '0' => ['two']],
            $reread->requests,
        );
    }

    public function test_it_leaves_out_empty_lists(): void
    {
        // Act

        $manifest = new ManifestFile(
            folders: ['' => ['users'], 'users' => []],
            requests: ['users' => [], '' => ['one']],
        );

        // Assert

        $this->assertSame(
            ['' => ['users']],
            $manifest->folders,
        );

        $this->assertSame(
            ['' => ['one']],
            $manifest->requests,
        );
    }

    public function test_it_is_built_from_entries_in_the_order_to_save(): void
    {
        // Arrange

        $entries = WorkspaceEntriesCollection::make([
            WorkspaceEntry::folder(FolderPath::fromStored('users')),
            WorkspaceEntry::folder(FolderPath::fromStored('users/admin')),
            WorkspaceEntry::request($this->requestFile(
                'one',
                'users',
            )),
        ]);

        // Act

        $manifest = ManifestFile::fromEntries($entries);

        // Assert

        $this->assertSame(
            ['' => ['users'], 'users' => ['admin']],
            $manifest->folders,
        );

        $this->assertSame(
            ['users' => ['one']],
            $manifest->requests,
        );
    }

    public function test_it_lists_the_identities_of_its_entries_in_the_saved_order(): void
    {
        // Arrange

        $manifest = new ManifestFile(
            folders: ['' => ['users', 'billing'], 'users' => ['admin']],
            requests: ['users' => ['one']],
        );

        // Act

        $identities = $manifest->entryIdentities();

        // Assert

        $this->assertSame(
            ['folder||users', 'folder||billing', 'folder|users|admin', 'request|users|one'],
            array_map(strval(...), $identities),
        );
    }

    public function test_it_adds_a_folder_and_the_folders_above_it_at_the_end(): void
    {
        // Arrange

        $manifest = new ManifestFile(folders: ['' => ['users'], 'users' => ['admin']]);

        // Act

        $updated = $manifest->addFolder(FolderPath::fromStored('users/staff/interns'));

        // Assert

        $this->assertSame(
            ['admin', 'staff'],
            $updated->folders['users'],
        );

        $this->assertSame(
            ['interns'],
            $updated->folders['users/staff'],
        );

        $this->assertSame(
            ['users'],
            $updated->folders[''],
        );
    }

    public function test_it_does_not_add_a_folder_twice(): void
    {
        // Arrange

        $manifest = new ManifestFile(folders: ['' => ['users'], 'users' => ['admin']]);

        // Act

        $updated = $manifest->addFolder(FolderPath::fromStored('users/admin'));

        // Assert

        $this->assertSame(
            $manifest->folders,
            $updated->folders,
        );
    }

    public function test_it_does_not_change_the_manifest_when_a_folder_is_added(): void
    {
        // Arrange

        $manifest = new ManifestFile(folders: ['' => ['users']]);

        // Act

        $updated = $manifest->addFolder(FolderPath::fromStored('billing'));

        // Assert

        $this->assertNotSame(
            $manifest,
            $updated,
        );

        $this->assertSame(
            ['' => ['users']],
            $manifest->folders,
        );
    }

    public function test_it_removes_a_folder_with_everything_inside_it(): void
    {
        // Arrange

        $manifest = new ManifestFile(
            folders: ['' => ['users', 'billing'], 'users' => ['admin'], 'users/admin' => ['deep']],
            requests: ['users' => ['one'], 'users/admin' => ['two'], 'billing' => ['three']],
        );

        // Act

        $updated = $manifest->removeFolder(FolderPath::fromStored('users'));

        // Assert

        $this->assertSame(
            ['' => ['billing']],
            $updated->folders,
        );

        $this->assertSame(
            ['billing' => ['three']],
            $updated->requests,
        );
    }

    public function test_it_drops_the_key_of_the_last_subfolder_and_keeps_the_parent_listed(): void
    {
        // Arrange

        $manifest = new ManifestFile(folders: ['' => ['users'], 'users' => ['admin']]);

        // Act

        $updated = $manifest->removeFolder(FolderPath::fromStored('users/admin'));

        // Assert

        $this->assertSame(
            ['' => ['users']],
            $updated->folders,
        );
    }

    public function test_it_does_not_change_the_manifest_when_a_folder_is_removed(): void
    {
        // Arrange

        $manifest = new ManifestFile(
            folders: ['' => ['users', 'billing']],
            requests: ['users' => ['one']],
        );

        // Act

        $updated = $manifest->removeFolder(FolderPath::fromStored('users'));

        // Assert

        $this->assertNotSame(
            $manifest,
            $updated,
        );

        $this->assertSame(
            ['' => ['users', 'billing']],
            $manifest->folders,
        );

        $this->assertSame(
            ['users' => ['one']],
            $manifest->requests,
        );
    }

    public function test_it_renames_a_folder_in_place_with_everything_inside_it(): void
    {
        // Arrange

        $manifest = new ManifestFile(
            folders: ['' => ['a', 'users', 'z'], 'users' => ['admin'], 'users/admin' => ['deep']],
            requests: ['users' => ['one'], 'users/admin' => ['two'], 'usersX' => ['three']],
        );

        // Act

        $updated = $manifest->withFolderRenamed(
            from: FolderPath::fromStored('users'),
            to: FolderPath::fromStored('people'),
        );

        // Assert

        $this->assertSame(
            ['' => ['a', 'people', 'z'], 'people' => ['admin'], 'people/admin' => ['deep']],
            $updated->folders,
        );

        $this->assertSame(
            ['people' => ['one'], 'people/admin' => ['two'], 'usersX' => ['three']],
            $updated->requests,
        );
    }

    public function test_it_renames_a_nested_folder_and_keeps_its_place_among_its_siblings(): void
    {
        // Arrange

        $manifest = new ManifestFile(
            folders: ['' => ['users'], 'users' => ['admin', 'billing', 'zed']],
            requests: ['users/billing' => ['one']],
        );

        // Act

        $updated = $manifest->withFolderRenamed(
            from: FolderPath::fromStored('users/billing'),
            to: FolderPath::fromStored('users/finance'),
        );

        // Assert

        $this->assertSame(
            ['' => ['users'], 'users' => ['admin', 'finance', 'zed']],
            $updated->folders,
        );

        $this->assertSame(
            ['users/finance' => ['one']],
            $updated->requests,
        );
    }

    public function test_it_does_not_change_the_manifest_when_a_folder_is_renamed(): void
    {
        // Arrange

        $manifest = new ManifestFile(
            folders: ['' => ['users']],
            requests: ['users' => ['one']],
        );

        // Act

        $updated = $manifest->withFolderRenamed(
            from: FolderPath::fromStored('users'),
            to: FolderPath::fromStored('people'),
        );

        // Assert

        $this->assertNotSame(
            $manifest,
            $updated,
        );

        $this->assertSame(
            ['' => ['users']],
            $manifest->folders,
        );

        $this->assertSame(
            ['users' => ['one']],
            $manifest->requests,
        );
    }

    public function test_it_adds_a_request_at_the_end_of_its_folder(): void
    {
        // Arrange

        $manifest = new ManifestFile(requests: ['users' => ['one']]);

        // Act

        $updated = $manifest->addRequest($this->requestFile(
            'two',
            'users',
        ));

        // Assert

        $this->assertSame(
            ['one', 'two'],
            $updated->requests['users'],
        );
    }

    public function test_it_does_not_add_a_request_twice(): void
    {
        // Arrange

        $manifest = new ManifestFile(requests: ['users' => ['one']]);

        // Act

        $updated = $manifest->addRequest($this->requestFile(
            'one',
            'users',
        ));

        // Assert

        $this->assertSame(
            ['one'],
            $updated->requests['users'],
        );
    }

    public function test_it_does_not_change_the_manifest_when_a_request_is_added(): void
    {
        // Arrange

        $manifest = new ManifestFile(requests: ['users' => ['one']]);

        // Act

        $updated = $manifest->addRequest($this->requestFile(
            'two',
            'users',
        ));

        // Assert

        $this->assertNotSame(
            $manifest,
            $updated,
        );

        $this->assertSame(
            ['one'],
            $manifest->requests['users'],
        );
    }

    public function test_it_removes_a_request_from_its_folder(): void
    {
        // Arrange

        $manifest = new ManifestFile(requests: ['users' => ['one', 'two'], '' => ['three']]);

        // Act

        $updated = $manifest->removeRequest($this->requestFile(
            'one',
            'users',
        ));

        // Assert

        $this->assertSame(
            ['two'],
            $updated->requests['users'],
        );

        $this->assertSame(
            ['three'],
            $updated->requests[''],
        );
    }

    public function test_it_drops_the_key_of_the_last_request_and_keeps_the_folder_listed(): void
    {
        // Arrange

        $manifest = new ManifestFile(
            folders: ['' => ['users']],
            requests: ['users' => ['one']],
        );

        // Act

        $updated = $manifest->removeRequest($this->requestFile(
            'one',
            'users',
        ));

        // Assert

        $this->assertSame(
            [],
            $updated->requests,
        );

        $this->assertSame(
            ['' => ['users']],
            $updated->folders,
        );
    }

    public function test_it_does_not_change_the_manifest_when_a_request_is_removed(): void
    {
        // Arrange

        $manifest = new ManifestFile(requests: ['users' => ['one', 'two']]);

        // Act

        $updated = $manifest->removeRequest($this->requestFile(
            'one',
            'users',
        ));

        // Assert

        $this->assertNotSame(
            $manifest,
            $updated,
        );

        $this->assertSame(
            ['one', 'two'],
            $manifest->requests['users'],
        );
    }

    public function test_it_turns_into_the_text_of_a_file(): void
    {
        // Arrange

        $manifest = new ManifestFile(
            folders: ['' => ['users'], 'users' => ['admin']],
            requests: ['' => ['one'], 'users/admin' => ['two']],
        );

        $expected = <<<'JSON'
{
  "folders": {
    "": [
      "users"
    ],
    "users": [
      "admin"
    ]
  },
  "requests": {
    "": [
      "one"
    ],
    "users/admin": [
      "two"
    ]
  }
}

JSON;

        // Act

        $json = $manifest->toJson();

        // Assert

        $this->assertSame(
            $expected,
            $json,
        );

        $this->assertSame(
            $expected,
            (string) $manifest,
        );
    }

    private function requestFile(string $id, string $folder): RequestFile
    {
        return new RequestFile(
            filepath: "/workspace/{$folder}/{$id}.request.json",
            relativeFilepath: "{$folder}/{$id}.request.json",
            folder: FolderPath::fromStored($folder),
            savedRequest: SavedRequest::fromArray(['id' => $id, 'name' => 'Request', 'method' => 'get']),
            hash: 'hash',
        );
    }
}
