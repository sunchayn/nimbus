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

    public function test_sync_persists_saved_requests_with_nested_body(): void
    {
        $payload = [
            'collections' => [
                [
                    'id' => 'flow-1',
                    'name' => 'Registration',
                    'variables' => [],
                    'requests' => [
                        [
                            'id' => 'req-1',
                            'name' => 'Create member',
                            'method' => 'POST',
                            'endpoint' => '/v2/api/members',
                            'payloadType' => 'json',
                            'headers' => [['key' => 'Accept', 'value' => 'application/json']],
                            'queryParameters' => [],
                            'body' => ['POST' => ['json' => '{"email":"jane@example.test"}']],
                            'authorization' => ['type' => 'bearer', 'value' => '{{gym_token}}'],
                        ],
                    ],
                ],
            ],
        ];

        $response = $this->putJson(route('nimbus.api.collections.sync'), $payload);

        $response->assertOk();
        $response->assertJsonPath('collections.0.requests.0.name', 'Create member');
        $response->assertJsonPath(
            'collections.0.requests.0.body.POST.json',
            '{"email":"jane@example.test"}',
        );
        $response->assertJsonPath('collections.0.requests.0.authorization.value', '{{gym_token}}');

        $this->getJson(route('nimbus.api.collections.index'))
            ->assertOk()
            ->assertJsonPath('collections.0.requests.0.endpoint', '/v2/api/members')
            ->assertJsonPath('collections.0.requests.0.authorization.value', '{{gym_token}}');
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

    public function test_sync_rejects_a_saved_request_missing_restore_fields(): void
    {
        // A saved request without headers/queryParameters/authorization would
        // crash when loaded into a tab, so it must be rejected at sync time.
        $response = $this->putJson(route('nimbus.api.collections.sync'), [
            'collections' => [
                [
                    'id' => 'flow-1',
                    'name' => 'Registration',
                    'variables' => [],
                    'requests' => [
                        [
                            'id' => 'req-1',
                            'name' => 'Create member',
                            'method' => 'POST',
                            'endpoint' => '/v2/api/members',
                            'payloadType' => 'json',
                            // headers, queryParameters, authorization omitted
                        ],
                    ],
                ],
            ],
        ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors([
            'collections.0.requests.0.headers',
            'collections.0.requests.0.queryParameters',
            'collections.0.requests.0.authorization',
        ]);
    }
}
