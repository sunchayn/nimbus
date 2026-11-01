<?php

declare(strict_types=1);

namespace Sunchayn\Nimbus\Console\Snapshot;

use Illuminate\Console\Command;
use Sunchayn\Nimbus\Modules\Routes\Services\Snapshot\ManifestManager;

class ClearRoutesSnapshotCommand extends Command
{
    protected $signature = 'nimbus:snapshot:clear';

    protected $description = 'Clear the stored routes snapshots of all applications.';

    public function handle(ManifestManager $manifestManager): int
    {
        $manifestManager->clearAll();

        $this->info('Nimbus routes snapshots cleared.');

        return self::SUCCESS;
    }
}
