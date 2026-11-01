<?php

declare(strict_types=1);

namespace Sunchayn\Nimbus\Tests\App\Console\Snapshot;

use PHPUnit\Framework\Attributes\CoversClass;
use Sunchayn\Nimbus\Console\Snapshot\ClearRoutesSnapshotCommand;
use Sunchayn\Nimbus\Modules\Routes\Services\Snapshot\ManifestManager;
use Sunchayn\Nimbus\Tests\TestCase;

#[CoversClass(ClearRoutesSnapshotCommand::class)]
class ClearRoutesSnapshotCommandFunctionalTest extends TestCase
{
    public function test_it_clears_all_snapshots(): void
    {
        // Arrange

        $manifestManagerSpy = $this->spy(ManifestManager::class);

        // Act

        $this->artisan('nimbus:snapshot:clear')->assertSuccessful();

        // Assert

        $manifestManagerSpy->shouldHaveReceived('clearAll')->once();
    }
}
