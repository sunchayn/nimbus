<?php

namespace Workbench\App\Jobs;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

/**
 * A queued job that the insight demo dispatches, so the panel has a job to list.
 */
class SyncInventoryJob implements ShouldQueue
{
    use Queueable;

    public function handle(): void {}
}
