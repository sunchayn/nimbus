<?php

declare(strict_types=1);

namespace Sunchayn\Nimbus\Modules\Magic\Services\Insights\Collectors;

use Illuminate\Contracts\Events\Dispatcher;
use Illuminate\Queue\Events\JobQueued;
use Sunchayn\Nimbus\Modules\Magic\Services\Insights\Results\JobsResult;
use Sunchayn\Nimbus\Modules\Magic\Services\Insights\Support\InsightValueLimiter;

/**
 * @phpstan-import-type JobEntry from JobsResult
 *
 * @extends ListCollector<JobEntry>
 */
class JobsCollector extends ListCollector
{
    public function __construct(
        private readonly Dispatcher $dispatcher,
        private readonly InsightValueLimiter $insightValueLimiter,
    ) {}

    public function start(): void
    {
        $this->dispatcher->listen(JobQueued::class, fn (JobQueued $event) => $this->record($event));
    }

    private function record(JobQueued $event): void
    {
        // The queue name is missing from the event in older Laravel versions.
        $queue = $event->queue ?? null;

        $this->add([
            'job' => (string) $this->insightValueLimiter->limit(is_object($event->job) ? $event->job::class : (string) $event->job),
            'queue' => is_string($queue) ? $queue : null,
            'connection' => $event->connectionName,
        ]);
    }

    public function collect(): JobsResult
    {
        return new JobsResult($this->entries, $this->dropped);
    }
}
