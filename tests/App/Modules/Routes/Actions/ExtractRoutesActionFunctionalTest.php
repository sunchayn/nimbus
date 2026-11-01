<?php

declare(strict_types=1);

namespace Sunchayn\Nimbus\Tests\App\Modules\Routes\Actions;

use PHPUnit\Framework\Attributes\CoversClass;
use RuntimeException;
use Sunchayn\Nimbus\Modules\Routes\Actions\ExtractRoutesAction;
use Sunchayn\Nimbus\Modules\Routes\Collections\ExtractedRoutesCollection;
use Sunchayn\Nimbus\Modules\Routes\Services\RoutesProcessors\Strategies\RoutesProcessorContract;
use Sunchayn\Nimbus\Modules\Routes\Services\Snapshot\ManifestManager;
use Sunchayn\Nimbus\Tests\TestCase;

#[CoversClass(ExtractRoutesAction::class)]
class ExtractRoutesActionFunctionalTest extends TestCase
{
    public function test_it_serves_the_stored_routes_when_the_manifest_is_fresh(): void
    {
        // Arrange

        config(['nimbus.snapshot' => true]);

        $stored = new ExtractedRoutesCollection;

        $manifestManagerSpy = $this->spy(ManifestManager::class);

        $processorSpy = $this->spy(RoutesProcessorContract::class);

        $action = resolve(ExtractRoutesAction::class);

        // Anticipate

        $manifestManagerSpy->shouldReceive('isStale')->andReturn(false);
        $manifestManagerSpy->shouldReceive('getExtractedRoutes')->andReturn($stored);

        // Act

        $routes = $action->execute();

        // Assert

        $this->assertSame($stored, $routes);

        $processorSpy->shouldNotHaveReceived('process');
        $manifestManagerSpy->shouldNotHaveReceived('save');
    }

    public function test_it_extracts_and_saves_when_the_manifest_is_stale(): void
    {
        // Arrange

        config(['nimbus.snapshot' => true]);

        $extracted = new ExtractedRoutesCollection;

        $manifestManagerSpy = $this->spy(ManifestManager::class);

        $processorSpy = $this->spy(RoutesProcessorContract::class);

        $action = resolve(ExtractRoutesAction::class);

        // Anticipate

        $manifestManagerSpy->shouldReceive('isStale')->andReturn(true);
        $processorSpy->shouldReceive('process')->andReturn($extracted);

        // Act

        $routes = $action->execute();

        // Assert

        $this->assertSame($extracted, $routes);

        $manifestManagerSpy->shouldHaveReceived('reset')->once();
        $manifestManagerSpy
            ->shouldHaveReceived('setExtractedRoutes')
            ->once()
            ->withArgs(function (ExtractedRoutesCollection $tracked) use ($extracted): bool {
                $this->assertSame($extracted, $tracked);

                return true;
            });
        $manifestManagerSpy->shouldHaveReceived('save')->once();
    }

    public function test_it_does_not_save_a_failed_extraction(): void
    {
        // Arrange

        config(['nimbus.snapshot' => true]);

        $manifestManagerSpy = $this->spy(ManifestManager::class);

        $processorSpy = $this->spy(RoutesProcessorContract::class);

        $action = resolve(ExtractRoutesAction::class);

        // Anticipate

        $manifestManagerSpy->shouldReceive('isStale')->andReturn(true);
        $processorSpy->shouldReceive('process')->andThrow(new RuntimeException('Extraction failed.'));

        // Act & Assert

        $this->expectException(RuntimeException::class);

        try {
            $action->execute();
        } finally {
            $manifestManagerSpy->shouldNotHaveReceived('save');
        }
    }

    public function test_it_skips_the_manifest_when_the_snapshot_is_disabled(): void
    {
        // Arrange

        config(['nimbus.snapshot' => false]);

        $extracted = new ExtractedRoutesCollection;

        $manifestManagerSpy = $this->spy(ManifestManager::class);

        $processorSpy = $this->spy(RoutesProcessorContract::class);

        $action = resolve(ExtractRoutesAction::class);

        // Anticipate

        $processorSpy->shouldReceive('process')->andReturn($extracted);

        // Act

        $routes = $action->execute();

        // Assert

        $this->assertSame($extracted, $routes);

        $manifestManagerSpy->shouldNotHaveReceived('isStale');
        $manifestManagerSpy->shouldNotHaveReceived('save');
    }
}
