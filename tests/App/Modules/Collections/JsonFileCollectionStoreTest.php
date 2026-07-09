<?php

namespace Sunchayn\Nimbus\Tests\App\Modules\Collections;

use Illuminate\Filesystem\Filesystem;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Sunchayn\Nimbus\Modules\Collections\DataTransferObjects\CollectionData;
use Sunchayn\Nimbus\Modules\Collections\Stores\JsonFileCollectionStore;

#[CoversClass(JsonFileCollectionStore::class)]
#[CoversClass(CollectionData::class)]
class JsonFileCollectionStoreTest extends TestCase
{
    private Filesystem $files;

    private string $directory;

    protected function setUp(): void
    {
        parent::setUp();

        $this->files = new Filesystem;
        $this->directory = sys_get_temp_dir().'/nimbus-collections-'.uniqid();
    }

    protected function tearDown(): void
    {
        $this->files->deleteDirectory($this->directory);

        parent::tearDown();
    }

    public function test_it_returns_empty_array_when_directory_is_absent(): void
    {
        $store = $this->store();

        $this->assertSame([], $store->all());
    }

    public function test_it_writes_and_reads_back_collections_preserving_order(): void
    {
        $store = $this->store();

        $store->sync([
            new CollectionData('id-a', 'Auth', [['key' => 'token', 'value' => 'abc', 'enabled' => true]]),
            new CollectionData('id-b', 'Billing', []),
        ]);

        $all = $store->all();

        $this->assertCount(2, $all);
        $this->assertSame('Auth', $all[0]->name);
        $this->assertSame('Billing', $all[1]->name);
        $this->assertSame('token', $all[0]->variables[0]['key']);
    }

    public function test_it_round_trips_saved_requests_with_nested_body(): void
    {
        $store = $this->store();

        $request = [
            'id' => 'req-1',
            'name' => 'Create member',
            'method' => 'POST',
            'endpoint' => '/v2/api/members',
            'payloadType' => 'json',
            'headers' => [['key' => 'Accept', 'value' => 'application/json']],
            'body' => ['POST' => ['json' => '{"email":"jane@example.test"}']],
            'authorization' => ['type' => 'bearer', 'value' => '{{gym_token}}'],
        ];

        $store->sync([
            new CollectionData('flow-1', 'Registration', [], [$request]),
        ]);

        $all = $store->all();

        $this->assertCount(1, $all);
        $this->assertCount(1, $all[0]->requests);
        $this->assertSame('Create member', $all[0]->requests[0]['name']);
        $this->assertSame(
            '{"email":"jane@example.test"}',
            $all[0]->requests[0]['body']['POST']['json'],
        );
    }

    public function test_sync_removes_collections_no_longer_present(): void
    {
        $store = $this->store();

        $store->sync([
            new CollectionData('id-a', 'Auth', []),
            new CollectionData('id-b', 'Billing', []),
        ]);

        $store->sync([
            new CollectionData('id-b', 'Billing', []),
        ]);

        $all = $store->all();

        $this->assertCount(1, $all);
        $this->assertSame('id-b', $all[0]->id);
        $this->assertFileDoesNotExist($this->directory.'/id-a.json');
    }

    public function test_it_persists_readable_pretty_json(): void
    {
        $store = $this->store();

        $store->sync([new CollectionData('id-a', 'Auth', [])]);

        $contents = $this->files->get($this->directory.'/id-a.json');

        $this->assertStringContainsString("\n", $contents);
        $this->assertStringContainsString('"name": "Auth"', $contents);
    }

    public function test_it_ignores_ids_that_would_escape_the_directory(): void
    {
        $store = $this->store();

        $store->sync([new CollectionData('../evil', 'Escape', [])]);

        $this->assertFileDoesNotExist($this->directory.'/../evil.json');
        // Sanitised to "evil.json" inside the directory, never a traversal.
        $this->assertFileExists($this->directory.'/evil.json');
    }

    public function test_it_persists_a_collection_whose_id_sanitises_to_nothing(): void
    {
        $store = $this->store();

        $store->sync([new CollectionData('###', 'Symbols', [])]);

        $this->assertCount(1, $store->all());
        $this->assertFileExists($this->directory.'/collection.json');
    }

    public function test_it_disambiguates_ids_that_would_collide(): void
    {
        $store = $this->store();

        // Both sanitise to "dup1"; neither may overwrite the other.
        $store->sync([
            new CollectionData('dup#1', 'First', []),
            new CollectionData('dup?1', 'Second', []),
        ]);

        $this->assertCount(2, $store->all());
        $this->assertCount(2, $this->files->glob($this->directory.'/*.json'));
    }

    public function test_prune_matches_kept_files_case_insensitively(): void
    {
        // On a case-insensitive filesystem, put("team.json") writes into an
        // existing "Team.json" entry, so prune must treat a kept name and a
        // case-variant on disk as the same file rather than deleting it.
        $this->files->ensureDirectoryExists($this->directory);
        $this->files->put(
            $this->directory.'/Team.json',
            json_encode(['id' => 'Team', 'name' => 'Team', 'variables' => []]),
        );

        $store = $this->store();
        $store->sync([new CollectionData('team', 'Team', [])]);

        $this->assertFileExists($this->directory.'/Team.json');
    }

    public function test_sync_leaves_malformed_files_untouched(): void
    {
        $this->files->ensureDirectoryExists($this->directory);
        $this->files->put($this->directory.'/hand-edited.json', '{ this is not valid json');

        $store = $this->store();
        $store->sync([new CollectionData('id-a', 'Auth', [])]);

        // The corrupt, hand-authored file is not destroyed by a sync.
        $this->assertFileExists($this->directory.'/hand-edited.json');
        // ...and it is skipped when reading (only the valid collection loads).
        $this->assertCount(1, $store->all());
        $this->assertSame('id-a', $store->all()[0]->id);
    }

    private function store(): JsonFileCollectionStore
    {
        return new JsonFileCollectionStore($this->files, $this->directory);
    }
}
