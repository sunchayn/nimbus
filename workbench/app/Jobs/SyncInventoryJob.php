<?php

namespace Workbench\App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;

/**
 * A queued job that the insight demo dispatches, so the panel has a job to list.
 */
class SyncInventoryJob implements ShouldQueue
{
    use Dispatchable;
    use Queueable;

    public function handle(): void {}
}
