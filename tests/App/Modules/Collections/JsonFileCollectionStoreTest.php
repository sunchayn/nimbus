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

    private function store(): JsonFileCollectionStore
    {
        return new JsonFileCollectionStore($this->files, $this->directory);
    }
}
