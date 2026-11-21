<?php

declare(strict_types=1);

namespace Sunchayn\Nimbus\Tests\App\Modules\Mcp\Services;

use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;
use PHPUnit\Framework\Attributes\CoversClass;
use Sunchayn\Nimbus\Modules\Mcp\Services\SentRequestStore;
use Sunchayn\Nimbus\Tests\Concerns\UsesTemporaryBasePath;
use Sunchayn\Nimbus\Tests\TestCase;

#[CoversClass(SentRequestStore::class)]
class SentRequestStoreFunctionalTest extends TestCase
{
    use UsesTemporaryBasePath;

    protected function setUp(): void
    {
        parent::setUp();

        $this->useTemporaryBasePath();
    }

    public function test_it_stores_a_run_and_finds_it_by_its_id(): void
    {
        // Arrange

        $store = resolve(SentRequestStore::class);

        // Act

        $id = $store->put(['route' => 'POST api/users']);

        $run = $store->find($id);

        // Assert

        $this->assertSame(
            ['id' => $id, 'route' => 'POST api/users'],
            $run,
        );
    }

    public function test_it_returns_nothing_for_an_unknown_id(): void
    {
        // Arrange

        $store = resolve(SentRequestStore::class);

        // Act

        $run = $store->find('unknown');

        // Assert

        $this->assertNull($run);
    }

    public function test_it_cannot_read_a_file_outside_its_folder(): void
    {
        // Arrange

        $store = resolve(SentRequestStore::class);

        // Act

        $run = $store->find('../../../composer');

        // Assert

        $this->assertNull($run);
    }

    public function test_it_keeps_only_the_latest_runs(): void
    {
        // Arrange

        $store = resolve(SentRequestStore::class);

        // Act

        for ($index = 0; $index < SentRequestStore::LIMIT + 5; $index++) {
            $store->put(['index' => $index]);
        }

        // Assert

        $files = glob(storage_path('nimbus/mcp-sent-requests/*.json'));

        $this->assertCount(
            SentRequestStore::LIMIT,
            $files,
        );
    }

    public function test_it_still_returns_an_id_and_logs_a_warning_when_it_cannot_store_the_run(): void
    {
        // Arrange

        File::ensureDirectoryExists(storage_path());

        // A file where the folder should be makes the write fail.
        file_put_contents(storage_path('nimbus'), 'blocked');

        Log::shouldReceive('warning')
            ->once()
            ->withArgs(fn (string $message): bool => str_contains($message, 'could not store'));

        $store = resolve(SentRequestStore::class);

        // Act

        $id = $store->put(['route' => 'POST api/users']);

        // Assert

        $this->assertNotSame('', $id);

        $this->assertNull($store->find($id));
    }
}
