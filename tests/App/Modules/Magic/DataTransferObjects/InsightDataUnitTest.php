<?php

namespace Sunchayn\Nimbus\Tests\App\Modules\Magic\DataTransferObjects;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Sunchayn\Nimbus\Modules\Magic\DataTransferObjects\InsightData;
use Sunchayn\Nimbus\Modules\Magic\Services\Insights\Results\CacheResult;
use Sunchayn\Nimbus\Modules\Magic\Services\Insights\Results\ExceptionResult;
use Sunchayn\Nimbus\Modules\Magic\Services\Insights\Results\HttpResult;
use Sunchayn\Nimbus\Modules\Magic\Services\Insights\Results\JobsResult;
use Sunchayn\Nimbus\Modules\Magic\Services\Insights\Results\LogsResult;
use Sunchayn\Nimbus\Modules\Magic\Services\Insights\Results\QueriesResult;

#[CoversClass(InsightData::class)]
class InsightDataUnitTest extends TestCase
{
    public function test_it_places_each_result_in_its_slot_and_leaves_the_rest_empty(): void
    {
        // Act

        $data = InsightData::fromResults(
            [new LogsResult([['level' => 'info', 'message' => 'a', 'context' => []]], 2)],
            peakMemory: 10,
            durationMs: 0.5,
        )->toArray();

        // Assert

        $this->assertSame('a', $data['logs'][0]['message']);
        $this->assertSame([], $data['queries']);
        $this->assertSame(['queries' => 0, 'logs' => 2, 'cache' => 0, 'jobs' => 0, 'mail' => 0, 'notifications' => 0, 'http' => 0, 'events' => 0], $data['dropped']);
        $this->assertSame(10, $data['peak_memory']);
        $this->assertSame([], $data['cache']);
        $this->assertSame([], $data['http']);
    }

    public function test_it_rebuilds_itself_from_its_own_array(): void
    {
        // Arrange

        $data = new InsightData(
            queries: new QueriesResult([['sql' => 's', 'bindings' => [], 'time_ms' => 1.0, 'offset_ms' => 0.0, 'connection' => 'c']], 4),
            jobs: new JobsResult([['job' => 'App\\Job', 'queue' => 'emails', 'connection' => 'redis']], 1),
            cache: new CacheResult([['operation' => 'hit', 'key' => 'k']], 2),
            peakMemory: 9,
            truncated: true,
        );

        // Act

        $rebuilt = InsightData::fromArray($data->toArray());

        // Assert

        $this->assertSame($data->toArray(), $rebuilt->toArray());
    }

    public function test_it_halves_the_larger_list_and_counts_what_it_dropped(): void
    {
        // Arrange

        $query = ['sql' => 's', 'bindings' => [], 'time_ms' => 1.0, 'offset_ms' => 0.0, 'connection' => 'c'];

        $data = new InsightData(queries: new QueriesResult([$query, $query, $query, $query, $query]));

        // Act

        $halved = $data->halved();

        // Assert

        $this->assertCount(2, $halved->queries->entries);
        $this->assertSame(3, $halved->queries->dropped);
        $this->assertTrue($halved->truncated);
    }

    public function test_it_cannot_be_halved_when_both_lists_are_empty(): void
    {
        // Act & Assert

        $this->assertNull((new InsightData)->halved());
    }

    public function test_it_halves_the_logs_when_they_outnumber_the_queries(): void
    {
        // Arrange

        $query = ['sql' => 's', 'bindings' => [], 'time_ms' => 1.0, 'offset_ms' => 0.0, 'connection' => 'c'];
        $log = ['level' => 'info', 'message' => 'm', 'context' => []];

        $data = new InsightData(
            queries: new QueriesResult([$query]),
            logs: new LogsResult([$log, $log, $log, $log], 1),
        );

        // Act

        $halved = $data->halved();

        // Assert

        $this->assertCount(1, $halved->queries->entries);
        $this->assertCount(2, $halved->logs->entries);
        $this->assertSame(3, $halved->logs->dropped);
        $this->assertSame(0, $halved->queries->dropped);
    }

    public function test_it_halves_a_list_other_than_queries_and_logs_when_it_is_the_longest(): void
    {
        // Arrange

        $call = ['method' => 'GET', 'url' => 'https://example.com', 'status' => 200, 'duration_ms' => 1.0, 'offset_ms' => 0.0];

        $data = new InsightData(
            queries: new QueriesResult([['sql' => 's', 'bindings' => [], 'time_ms' => 1.0, 'offset_ms' => 0.0, 'connection' => 'c']]),
            http: new HttpResult([$call, $call, $call, $call], 1),
        );

        // Act

        $halved = $data->halved();

        // Assert

        $this->assertCount(2, $halved->http->entries);
        $this->assertSame(3, $halved->http->dropped);
        $this->assertCount(1, $halved->queries->entries);
        $this->assertTrue($halved->truncated);
    }

    public function test_it_keeps_each_kind_under_its_own_key_and_drop_count(): void
    {
        // Arrange

        $data = new InsightData(
            cache: new CacheResult([['operation' => 'forget', 'key' => 'k']], 5),
            jobs: new JobsResult([['job' => 'App\\Job', 'queue' => null, 'connection' => null]], 6),
        );

        // Act

        $array = $data->toArray();

        // Assert

        $this->assertSame([['operation' => 'forget', 'key' => 'k']], $array['cache']);
        $this->assertSame([['job' => 'App\\Job', 'queue' => null, 'connection' => null]], $array['jobs']);
        $this->assertSame(5, $array['dropped']['cache']);
        $this->assertSame(6, $array['dropped']['jobs']);
        $this->assertArrayNotHasKey('effects', $array);
    }

    public function test_it_halves_the_queries_when_both_lists_have_the_same_size(): void
    {
        // Arrange

        $query = ['sql' => 's', 'bindings' => [], 'time_ms' => 1.0, 'offset_ms' => 0.0, 'connection' => 'c'];
        $log = ['level' => 'info', 'message' => 'm', 'context' => []];

        $data = new InsightData(
            queries: new QueriesResult([$query, $query]),
            logs: new LogsResult([$log, $log]),
        );

        // Act

        $halved = $data->halved();

        // Assert

        $this->assertCount(1, $halved->queries->entries);
        $this->assertCount(2, $halved->logs->entries);
    }

    public function test_it_keeps_everything_else_when_it_is_halved(): void
    {
        // Arrange

        $query = ['sql' => 's', 'bindings' => [], 'time_ms' => 1.0, 'offset_ms' => 0.0, 'connection' => 'c'];

        $data = new InsightData(
            queries: new QueriesResult([$query, $query]),
            cache: new CacheResult([['operation' => 'hit', 'key' => 'k']], 2),
            peakMemory: 7,
            durationMs: 1.5,
        );

        // Act

        $halved = $data->halved();

        // Assert

        $this->assertSame($data->cache, $halved->cache);
        $this->assertSame(7, $halved->peakMemory);
        $this->assertSame(1.5, $halved->durationMs);
    }

    public function test_it_does_not_change_the_original_when_it_is_halved(): void
    {
        // Arrange

        $query = ['sql' => 's', 'bindings' => [], 'time_ms' => 1.0, 'offset_ms' => 0.0, 'connection' => 'c'];

        $data = new InsightData(queries: new QueriesResult([$query, $query]));

        // Act

        $data->halved();

        // Assert

        $this->assertCount(2, $data->queries->entries);
        $this->assertFalse($data->truncated);
    }

    public function test_it_can_drop_the_exception_and_keep_the_rest(): void
    {
        // Arrange

        $query = ['sql' => 's', 'bindings' => [], 'time_ms' => 1.0, 'offset_ms' => 0.0, 'connection' => 'c'];

        $data = new InsightData(
            queries: new QueriesResult([$query], 2),
            exception: new ExceptionResult(['class' => 'E', 'message' => 'm', 'file' => 'f', 'line' => 1, 'trace' => []]),
            peakMemory: 7,
            truncated: true,
        );

        // Act

        $without = $data->withoutException()->toArray();

        // Assert

        $this->assertNull($without['exception']);
        $this->assertCount(1, $without['queries']);
        $this->assertSame(2, $without['dropped']['queries']);
        $this->assertSame(7, $without['peak_memory']);
        $this->assertTrue($without['truncated']);
    }

    public function test_it_is_empty_by_default(): void
    {
        // Act

        $data = (new InsightData)->toArray();

        // Assert

        $this->assertSame([], $data['queries']);
        $this->assertSame([], $data['logs']);
        $this->assertSame([], $data['gates']);
        $this->assertNull($data['exception']);
        $this->assertSame(0, $data['peak_memory']);
        $this->assertSame(0.0, $data['duration_ms']);
        $this->assertSame(['queries' => 0, 'logs' => 0, 'cache' => 0, 'jobs' => 0, 'mail' => 0, 'notifications' => 0, 'http' => 0, 'events' => 0], $data['dropped']);
        $this->assertFalse($data['truncated']);
        $this->assertSame([], $data['cache']);
        $this->assertSame([], $data['http']);
    }

    public function test_it_rebuilds_an_empty_array_with_defaults(): void
    {
        // Act

        $rebuilt = InsightData::fromArray([]);

        // Assert

        $this->assertSame((new InsightData)->toArray(), $rebuilt->toArray());
    }

    public function test_it_rebuilds_the_gates_and_the_exception(): void
    {
        // Arrange

        $gate = ['ability' => 'update', 'result' => false, 'arguments' => ['App\\Post'], 'user' => '5'];
        $exception = ['class' => 'E', 'message' => 'm', 'file' => 'f', 'line' => 1, 'trace' => []];

        // Act

        $rebuilt = InsightData::fromArray([
            'gates' => [$gate],
            'exception' => $exception,
            'duration_ms' => '2.5',
            'peak_memory' => '12',
        ]);

        // Assert

        $this->assertSame([$gate], $rebuilt->gates->gates);
        $this->assertSame($exception, $rebuilt->exception->exception);
        $this->assertSame(2.5, $rebuilt->durationMs);
        $this->assertSame(12, $rebuilt->peakMemory);
    }

    public function test_it_uses_the_first_result_of_a_kind_when_there_are_several(): void
    {
        // Arrange

        $first = new LogsResult([['level' => 'info', 'message' => 'first', 'context' => []]]);
        $second = new LogsResult([['level' => 'info', 'message' => 'second', 'context' => []]]);

        // Act

        $data = InsightData::fromResults([$first, $second], peakMemory: 0, durationMs: 0.0);

        // Assert

        $this->assertSame($first, $data->logs);
    }
}
