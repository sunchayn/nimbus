<?php

declare(strict_types=1);

namespace Sunchayn\Nimbus\Tests\App\Modules\Routes\Services\Snapshot;

use Illuminate\Support\Facades\Route;
use LogicException;
use PHPUnit\Framework\Attributes\CoversClass;
use Sunchayn\Nimbus\Modules\Ast\Events\ClassFileResolved;
use Sunchayn\Nimbus\Modules\Config\ActiveApplicationResolver;
use Sunchayn\Nimbus\Modules\Routes\Collections\ExtractedRoutesCollection;
use Sunchayn\Nimbus\Modules\Routes\Services\Snapshot\ManifestManager;
use Sunchayn\Nimbus\Tests\TestCase;

#[CoversClass(ManifestManager::class)]
class ManifestManagerFunctionalTest extends TestCase
{
    private string $file;

    private ManifestManager $manager;

    protected function setUp(): void
    {
        parent::setUp();

        $this->file = tempnam(sys_get_temp_dir(), 'nimbus_manifest_');
        file_put_contents($this->file, 'first');
        // An old time makes the file trusted by the time check.
        touch($this->file, time() - 100);

        $this->manager = resolve(ManifestManager::class);
        $this->manager->reset();
    }

    protected function tearDown(): void
    {
        @unlink($this->file);

        $this->manager->clearAll();

        parent::tearDown();
    }

    public function test_it_saves_the_tracked_files_in_the_manifest(): void
    {
        // Arrange

        $this->manager->trackFile(new ClassFileResolved($this->file));
        $this->manager->trackFile(new ClassFileResolved($this->file));
        $this->manager->trackFile(new ClassFileResolved(base_path('vendor/pkg/Bar.php')));

        $extracted = new ExtractedRoutesCollection;

        // Act

        $this->manager->setExtractedRoutes($extracted);

        $this->manager->save();

        // Assert

        $manifest = $this->manager->load();

        $this->assertSame([$this->file], $this->trackedFiles($manifest));
        $this->assertSame([5, filemtime($this->file)], $manifest['files'][$this->file]);
        $this->assertSame($extracted, $this->manager->getExtractedRoutes());
    }

    public function test_it_forgets_tracked_files_on_reset(): void
    {
        // Arrange

        $this->manager->trackFile(new ClassFileResolved($this->file));

        // Act

        $this->manager->reset();

        $this->manager->setExtractedRoutes(new ExtractedRoutesCollection);

        $this->manager->save();

        // Assert

        $this->assertSame([], $this->trackedFiles($this->manager->load()));
    }

    public function test_it_adds_the_openapi_files_to_the_manifest(): void
    {
        // Arrange

        $spec = tempnam(sys_get_temp_dir(), 'nimbus_spec_');
        file_put_contents($spec, 'openapi: 3.0.0');

        config([
            'nimbus.applications.main.routes.strategy' => 'openapi',
            'nimbus.applications.main.routes.openapi.files' => ['default' => $spec],
        ]);

        $this->app->forgetInstance(ActiveApplicationResolver::class);
        $this->app->forgetInstance(ManifestManager::class);

        $manager = resolve(ManifestManager::class);

        // Act

        $manager->setExtractedRoutes(new ExtractedRoutesCollection);

        $manager->save();

        // Assert

        $this->assertArrayHasKey($spec, $manager->load()['files']);

        @unlink($spec);
    }

    public function test_it_refuses_to_save_without_tracked_routes(): void
    {
        // Arrange

        $this->expectException(LogicException::class);

        // Act

        $this->manager->save();
    }

    public function test_it_is_stale_without_a_manifest(): void
    {
        // Act & Assert

        $this->assertTrue($this->manager->isStale());
    }

    public function test_it_is_not_stale_when_nothing_changed(): void
    {
        // Arrange

        $this->saveTrackedManifest();

        // Act & Assert

        $this->assertFalse($this->manager->isStale());
    }

    public function test_it_is_stale_when_the_application_config_changes(): void
    {
        // Arrange

        $this->saveTrackedManifest();

        config(['nimbus.applications.main.name' => 'Another name']);

        // Act & Assert

        $this->assertTrue($this->manager->isStale());
    }

    public function test_it_is_stale_when_a_route_under_the_prefix_is_added(): void
    {
        // Arrange

        $this->saveTrackedManifest();

        Route::get(resolve(ActiveApplicationResolver::class)->getRoutesPrefix().'/manifest-test', fn () => 'ok');

        // Act & Assert

        $this->assertTrue($this->manager->isStale());
    }

    public function test_it_ignores_a_route_outside_the_prefix(): void
    {
        // Arrange

        $this->saveTrackedManifest();

        Route::get('outside-the-prefix/manifest-test', fn () => 'ok');

        // Act & Assert

        $this->assertFalse($this->manager->isStale());
    }

    public function test_it_is_stale_when_a_file_changes_size(): void
    {
        // Arrange

        $this->saveTrackedManifest();

        $time = filemtime($this->file);

        file_put_contents($this->file, 'a longer content');

        touch($this->file, $time);

        // Act & Assert

        $this->assertTrue($this->manager->isStale());
    }

    public function test_it_is_stale_when_a_file_is_touched(): void
    {
        // Arrange

        $this->saveTrackedManifest();

        touch($this->file, time() - 50);

        // Act & Assert

        $this->assertTrue($this->manager->isStale());
    }

    public function test_it_is_stale_when_a_file_is_deleted(): void
    {
        // Arrange

        $this->saveTrackedManifest();

        unlink($this->file);

        // Act & Assert

        $this->assertTrue($this->manager->isStale());
    }

    public function test_it_does_not_trust_a_file_modified_close_to_the_manifest(): void
    {
        // Arrange

        touch($this->file, time());

        $this->saveTrackedManifest();

        // Act & Assert

        $this->assertTrue($this->manager->isStale());
    }

    public function test_it_loads_a_saved_manifest_in_a_later_request(): void
    {
        // Arrange

        $this->saveTrackedManifest();

        $this->app->forgetInstance(ManifestManager::class);

        // Act

        $reloaded = resolve(ManifestManager::class);

        // Assert

        $this->assertNotNull($reloaded->load());
        $this->assertSame($this->manager->createdAt(), $reloaded->createdAt());
        $this->assertFalse($reloaded->isStale());
    }

    public function test_it_forgets_a_cleared_manifest(): void
    {
        // Arrange

        $this->saveTrackedManifest();

        // Act

        $this->manager->clear();

        $this->app->forgetInstance(ManifestManager::class);

        // Assert

        $this->assertNull(resolve(ManifestManager::class)->load());
    }

    public function test_it_treats_a_corrupt_manifest_as_missing(): void
    {
        // Arrange

        $this->saveTrackedManifest();

        file_put_contents(base_path('vendor/sunchayn/nimbus/storage/routes_snapshot_main.snapshot'), 'not a manifest');

        $this->app->forgetInstance(ManifestManager::class);

        $manager = resolve(ManifestManager::class);

        // Act

        $manifest = $manager->load();

        // Assert

        $this->assertNull($manifest);
        $this->assertTrue($manager->isStale());
    }

    public function test_it_does_not_fail_when_the_manifest_cannot_be_written(): void
    {
        // Arrange

        $path = base_path('vendor/sunchayn/nimbus/storage/routes_snapshot_main.snapshot');

        // A directory in place of the file makes the final rename fail.
        mkdir($path, 0755, true);

        // Act

        $this->manager->setExtractedRoutes(new ExtractedRoutesCollection);

        $this->manager->save();

        // Assert

        $this->assertDirectoryExists($path);

        rmdir($path);
    }

    /*
     * Helpers.
     */

    private function saveTrackedManifest(): void
    {
        $this->manager->trackFile(new ClassFileResolved($this->file));

        $this->manager->setExtractedRoutes(new ExtractedRoutesCollection);

        $this->manager->save();
    }

    /**
     * @param  array{files: array<string, array{int, int}>}|null  $manifest
     * @return string[]
     */
    private function trackedFiles(?array $manifest): array
    {
        return array_values(array_diff(array_keys($manifest['files'] ?? []), [base_path('composer.lock')]));
    }
}
