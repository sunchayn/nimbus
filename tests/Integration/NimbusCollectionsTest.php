<?php

namespace Sunchayn\Nimbus\Tests\Integration;

use Illuminate\Filesystem\Filesystem;
use PHPUnit\Framework\Attributes\CoversClass;
use Sunchayn\Nimbus\Http\Api\Collections\NimbusCollectionsController;
use Sunchayn\Nimbus\Http\Api\Collections\SyncCollectionsRequest;
use Sunchayn\Nimbus\Modules\Collections\Stores\JsonFileCollectionStore;
use Sunchayn\Nimbus\NimbusServiceProvider;
use Sunchayn\Nimbus\Tests\TestCase;

#[CoversClass(NimbusCollectionsController::class)]
#[CoversClass(SyncCollectionsRequest::class)]
#[CoversClass(JsonFileCollectionStore::class)]
#[CoversClass(NimbusServiceProvider::class)]
class NimbusCollectionsTest extends TestCase
{
    private string $directory;

    protected function setUp(): void
    {
        parent::setUp();

        $this->directory = sys_get_temp_dir().'/nimbus-collections-endpoint-'.uniqid();
        config()->set('nimbus.collections.driver', 'json');
        config()->set('nimbus.collections.path', $this->directory);
    }

    protected function tearDown(): void
    {
        (new Filesystem)->deleteDirectory($this->directory);

        parent::tearDown();
    }

    public function test_index_returns_empty_collections_initially(): void
    {
        $response = $this->getJson(route('nimbus.api.collections.index'));

        $response->assertOk();
        $response->assertExactJson(['collections' => []]);
    }

    public function test_sync_persists_collections_and_returns_them(): void
    {
        $payload = [
            'collections' => [
                [
                    'id' => 'id-a',
                    'name' => 'Auth',
                    'variables' => [
                        ['id' => 1, 'type' => 'text', 'key' => 'token', 'value' => 'abc', 'enabled' => true],
                    ],
                ],
            ],
        ];

        $response = $this->putJson(route('nimbus.api.collections.sync'), $payload);

        $response->assertOk();
        $response->assertJsonPath('collections.0.id', 'id-a');
        $response->assertJsonPath('collections.0.name', 'Auth');
        $response->assertJsonPath('collections.0.variables.0.key', 'token');

        $this->assertFileExists($this->directory.'/id-a.json');

        // And a subsequent read returns the same set.
        $this->getJson(route('nimbus.api.collections.index'))
            ->assertOk()
            ->assertJsonPath('collections.0.id', 'id-a');
    }

    public function test_sync_requires_id_and_name_for_each_collection(): void
    {
        $response = $this->putJson(route('nimbus.api.collections.sync'), [
            'collections' => [
                ['variables' => []],
            ],
        ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors([
            'collections.0.id',
            'collections.0.name',
        ]);
    }
}
