<?php

namespace Sunchayn\Nimbus\Tests\App\Modules\Magic\Services\Insights\Collectors;

use ArrayObject;
use Illuminate\Queue\Events\JobQueued;
use PHPUnit\Framework\Attributes\CoversClass;
use ReflectionClass;
use Sunchayn\Nimbus\Modules\Magic\Services\Insights\Collectors\JobsCollector;
use Sunchayn\Nimbus\Tests\TestCase;

#[CoversClass(JobsCollector::class)]
class JobsCollectorFunctionalTest extends TestCase
{
    public function test_it_records_a_queued_job_with_its_queue_and_connection(): void
    {
        // Arrange

        $collector = resolve(JobsCollector::class);
        $collector->start();

        // Act

        event($this->jobQueued('App\\Jobs\\Named', 'emails'));

        $result = $collector->collect();

        // Assert

        $this->assertSame('App\\Jobs\\Named', $result->entries[0]['job']);
        $this->assertSame('redis', $result->entries[0]['connection']);
        $this->assertSame(property_exists(JobQueued::class, 'queue') ? 'emails' : null, $result->entries[0]['queue']);
    }

    public function test_it_names_a_job_object_by_its_class(): void
    {
        // Arrange

        $collector = resolve(JobsCollector::class);
        $collector->start();

        // Act

        event($this->jobQueued(new ArrayObject, null));

        $result = $collector->collect();

        // Assert

        $this->assertSame(ArrayObject::class, $result->entries[0]['job']);
        $this->assertNull($result->entries[0]['queue']);
    }

    public function test_it_caps_the_entries_and_counts_the_excess(): void
    {
        // Arrange

        $collector = resolve(JobsCollector::class);
        $collector->start();

        foreach (range(1, JobsCollector::MAX_ENTRIES + 1) as $i) {
            event($this->jobQueued('App\\Jobs\\Named', null));
        }

        // Act

        $result = $collector->collect();

        // Assert

        $this->assertCount(JobsCollector::MAX_ENTRIES, $result->entries);
        $this->assertSame(1, $result->dropped);
    }

    /**
     * The constructor of the event changed between Laravel versions, so it is built without it.
     */
    private function jobQueued(string|object $job, ?string $queue): JobQueued
    {
        $event = (new ReflectionClass(JobQueued::class))->newInstanceWithoutConstructor();

        $event->connectionName = 'redis';
        $event->job = $job;

        if (property_exists($event, 'queue')) {
            $event->queue = $queue;
        }

        return $event;
    }
}
