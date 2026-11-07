<?php

namespace Sunchayn\Nimbus\Tests\App\Modules\Magic\Services\Insights\Collectors;

use Illuminate\Database\Events\QueryExecuted;
use Illuminate\Support\Facades\DB;
use PHPUnit\Framework\Attributes\CoversClass;
use Sunchayn\Nimbus\Modules\Magic\Services\Insights\Collectors\QueriesCollector;
use Sunchayn\Nimbus\Modules\Magic\Services\Insights\Support\InsightClock;
use Sunchayn\Nimbus\Modules\Magic\Services\Insights\Support\InsightValueLimiter;
use Sunchayn\Nimbus\Modules\Magic\Services\Insights\Support\SecretBindingMasker;
use Sunchayn\Nimbus\Tests\TestCase;

#[CoversClass(QueriesCollector::class)]
class QueriesCollectorFunctionalTest extends TestCase
{
    public function test_it_collects_the_queries_that_run_after_it_starts(): void
    {
        // Arrange

        $collector = resolve(QueriesCollector::class);
        $collector->start();

        // Act

        DB::select('select ? as value', [7]);

        $result = $collector->collect();

        // Assert

        $this->assertCount(1, $result->entries);
        $this->assertSame('select ? as value', $result->entries[0]['sql']);
        $this->assertSame([7], $result->entries[0]['bindings']);
        $this->assertSame(0, $result->dropped);
    }

    public function test_it_records_when_each_query_started_on_the_request_clock(): void
    {
        // Arrange

        $clock = resolve(InsightClock::class);
        $clock->start();

        $collector = resolve(QueriesCollector::class);
        $collector->start();

        // Act

        DB::select('select 1');
        usleep(5_000);
        DB::select('select 2');

        $elapsed = $clock->elapsedMs();
        $result = $collector->collect();

        // Assert

        $this->assertGreaterThanOrEqual(0.0, $result->entries[0]['offset_ms']);
        $this->assertGreaterThan($result->entries[0]['offset_ms'], $result->entries[1]['offset_ms']);
        $this->assertLessThanOrEqual($elapsed, $result->entries[1]['offset_ms'] + $result->entries[1]['time_ms']);
    }

    public function test_it_starts_a_query_before_the_moment_its_event_fires(): void
    {
        // Arrange

        $collector = resolve(QueriesCollector::class);
        $collector->start();

        resolve(InsightClock::class)->start();

        usleep(80_000);

        $before = resolve(InsightClock::class)->elapsedMs();

        // Act

        event(new QueryExecuted('select 1', [], 50.0, DB::connection()));

        $after = resolve(InsightClock::class)->elapsedMs();
        $offset = $collector->collect()->entries[0]['offset_ms'];

        // Assert

        $this->assertLessThanOrEqual($after - 50.0 + 0.01, $offset);
        $this->assertGreaterThanOrEqual(max(0.0, $before - 50.0) - 0.01, $offset);
    }

    public function test_it_caps_queries_and_counts_the_excess(): void
    {
        // Arrange

        $collector = resolve(QueriesCollector::class);
        $collector->start();

        foreach (range(1, QueriesCollector::MAX_QUERIES + 3) as $i) {
            DB::select('select 1');
        }

        // Act

        $result = $collector->collect();

        // Assert

        $this->assertCount(QueriesCollector::MAX_QUERIES, $result->entries);
        $this->assertSame(3, $result->dropped);
    }

    public function test_it_masks_secret_bindings_in_production(): void
    {
        // Arrange

        $this->app['env'] = 'production';

        $collector = resolve(QueriesCollector::class);
        $collector->start();

        // Act

        DB::select("select * from (select 'a' as name, 'b' as password) where name = ? and password = ?", ['users', 'hunter2']);

        $result = $collector->collect();

        // Assert

        $this->assertSame(['users', SecretBindingMasker::MASK], $result->entries[0]['bindings']);
    }

    public function test_it_keeps_secret_bindings_outside_production(): void
    {
        // Arrange

        $collector = resolve(QueriesCollector::class);
        $collector->start();

        // Act

        DB::select("select * from (select 'a' as name, 'b' as password) where name = ? and password = ?", ['users', 'hunter2']);

        $result = $collector->collect();

        // Assert

        $this->assertSame(['users', 'hunter2'], $result->entries[0]['bindings']);
    }

    public function test_it_records_the_time_and_the_connection_of_a_query(): void
    {
        // Arrange

        $collector = resolve(QueriesCollector::class);
        $collector->start();

        // Act

        event(new QueryExecuted('select 1', [], 1.2345, DB::connection()));

        $result = $collector->collect();

        // Assert

        $this->assertSame(1.23, $result->entries[0]['time_ms']);
        $this->assertSame(DB::connection()->getName(), $result->entries[0]['connection']);
    }

    public function test_it_limits_the_bindings_to_short_scalars(): void
    {
        // Arrange

        $collector = resolve(QueriesCollector::class);
        $collector->start();

        // Act

        DB::select('select ?, ?, ?, ?', [str_repeat('a', InsightValueLimiter::LIMIT + 10), null, true, 1.5]);

        $result = $collector->collect();

        // Assert

        $this->assertSame([str_repeat('a', InsightValueLimiter::LIMIT), null, true, 1.5], $result->entries[0]['bindings']);
    }

    public function test_it_collects_nothing_before_it_starts(): void
    {
        // Arrange

        $collector = resolve(QueriesCollector::class);

        // Act

        DB::select('select 1');

        $result = $collector->collect();

        // Assert

        $this->assertSame([], $result->entries);
    }
}
