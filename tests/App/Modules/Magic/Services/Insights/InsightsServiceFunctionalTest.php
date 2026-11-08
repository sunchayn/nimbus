<?php

namespace Sunchayn\Nimbus\Tests\App\Modules\Magic\Services\Insights;

use Illuminate\Database\Events\QueryExecuted;
use Illuminate\Foundation\Http\Events\RequestHandled;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;
use PHPUnit\Framework\Attributes\CoversClass;
use Sunchayn\Nimbus\Modules\Magic\Services\Insights\Collectors\CacheCollector;
use Sunchayn\Nimbus\Modules\Magic\Services\Insights\Collectors\EventsCollector;
use Sunchayn\Nimbus\Modules\Magic\Services\Insights\Collectors\ExceptionCollector;
use Sunchayn\Nimbus\Modules\Magic\Services\Insights\Collectors\GatesCollector;
use Sunchayn\Nimbus\Modules\Magic\Services\Insights\Collectors\HttpCollector;
use Sunchayn\Nimbus\Modules\Magic\Services\Insights\Collectors\InsightCollectorContract;
use Sunchayn\Nimbus\Modules\Magic\Services\Insights\Collectors\JobsCollector;
use Sunchayn\Nimbus\Modules\Magic\Services\Insights\Collectors\LogsCollector;
use Sunchayn\Nimbus\Modules\Magic\Services\Insights\Collectors\MailCollector;
use Sunchayn\Nimbus\Modules\Magic\Services\Insights\Collectors\NotificationsCollector;
use Sunchayn\Nimbus\Modules\Magic\Services\Insights\Collectors\QueriesCollector;
use Sunchayn\Nimbus\Modules\Magic\Services\Insights\InsightsService;
use Sunchayn\Nimbus\Modules\Magic\Services\Insights\InsightStore;
use Sunchayn\Nimbus\Modules\Magic\Services\Insights\Results\ExceptionResult;
use Sunchayn\Nimbus\Modules\Magic\Services\Insights\Results\InsightCollectorResult;
use Sunchayn\Nimbus\Modules\Magic\Services\Insights\Results\LogsResult;
use Sunchayn\Nimbus\Modules\Magic\Services\Insights\Results\QueriesResult;
use Sunchayn\Nimbus\Tests\TestCase;

#[CoversClass(InsightsService::class)]
class InsightsServiceFunctionalTest extends TestCase
{
    private const ID = '99999999-8888-7777-6666-555555555555';

    protected function tearDown(): void
    {
        resolve(InsightStore::class)->pull(self::ID);

        parent::tearDown();
    }

    public function test_it_runs_every_collector_and_merges_what_they_return(): void
    {
        // Arrange

        $first = $this->makeCollector(new LogsResult([['level' => 'info', 'message' => 'a', 'context' => []]], 1));
        $second = $this->makeCollector(new ExceptionResult(['class' => 'E', 'message' => 'm', 'file' => 'f', 'line' => 1, 'trace' => []]));

        $service = resolve(InsightsService::class, ['collectors' => [$first, $second]]);

        // Act

        $service->start(self::ID);
        $service->flush();

        $insight = resolve(InsightStore::class)->pull(self::ID);

        // Assert

        $this->assertTrue($first->started);
        $this->assertTrue($second->started);
        $this->assertSame('a', $insight['logs'][0]['message']);
        $this->assertSame('m', $insight['exception']['message']);
        $this->assertSame(['queries' => 0, 'logs' => 1, 'cache' => 0, 'jobs' => 0, 'mail' => 0, 'notifications' => 0, 'http' => 0, 'events' => 0], $insight['dropped']);
        $this->assertFalse($insight['truncated']);
    }

    public function test_it_writes_what_the_sandbox_capture_returns(): void
    {
        // Arrange

        $service = resolve(InsightsService::class, ['collectors' => []]);

        $service->captureSandbox(fn (): array => ['jobs' => [['job' => 'App\\Jobs\\ShipOrder', 'queue' => 'default']]]);

        // Act

        $service->start(self::ID);
        $service->flush();

        $insight = resolve(InsightStore::class)->pull(self::ID);

        // Assert

        $this->assertSame(['jobs' => [['job' => 'App\\Jobs\\ShipOrder', 'queue' => 'default']]], $insight['sandbox']);
    }

    public function test_it_writes_an_empty_sandbox_without_a_capture(): void
    {
        // Arrange

        $service = resolve(InsightsService::class, ['collectors' => []]);

        // Act

        $service->start(self::ID);
        $service->flush();

        $insight = resolve(InsightStore::class)->pull(self::ID);

        // Assert

        $this->assertSame([], $insight['sandbox']);
    }

    public function test_it_records_the_duration_in_milliseconds_and_the_peak_memory(): void
    {
        // Arrange

        $service = resolve(InsightsService::class, ['collectors' => []]);

        // Act

        $service->start(self::ID);

        usleep(20_000);

        $service->flush();

        $insight = resolve(InsightStore::class)->pull(self::ID);

        // Assert

        $this->assertGreaterThan(0, $insight['peak_memory']);
        $this->assertGreaterThanOrEqual(20, $insight['duration_ms']);
    }

    public function test_it_writes_the_insight_when_the_request_is_handled(): void
    {
        // Arrange

        $service = resolve(InsightsService::class, [
            'collectors' => [$this->makeCollector(new LogsResult([['level' => 'info', 'message' => 'handled', 'context' => []]]))],
        ]);

        $service->start(self::ID);

        // Act

        event(new RequestHandled(Request::create('/'), new Response));

        $insight = resolve(InsightStore::class)->pull(self::ID);

        // Assert

        $this->assertSame('handled', $insight['logs'][0]['message']);
    }

    public function test_it_does_not_write_anything_before_the_request_is_handled(): void
    {
        // Arrange

        $service = resolve(InsightsService::class, ['collectors' => []]);

        // Act

        $service->start(self::ID);

        // Assert

        $this->assertNull(resolve(InsightStore::class)->pull(self::ID));
    }

    public function test_it_resolves_the_registered_collectors_from_the_container(): void
    {
        // Arrange

        $service = resolve(InsightsService::class);
        $service->start(self::ID);

        event(new QueryExecuted('select 1', [], 1.0, DB::connection()));

        // Act

        $service->flush();

        // Assert

        $this->assertSame('select 1', resolve(InsightStore::class)->pull(self::ID)['queries'][0]['sql']);
    }

    public function test_it_has_every_collector_registered_in_the_container(): void
    {
        // Act

        $collectors = array_map(
            fn (InsightCollectorContract $collector): string => $collector::class,
            iterator_to_array($this->app->tagged(InsightsService::COLLECTORS_TAG), false),
        );

        // Assert

        $this->assertEqualsCanonicalizing(
            [
                QueriesCollector::class,
                LogsCollector::class,
                GatesCollector::class,
                ExceptionCollector::class,
                CacheCollector::class,
                JobsCollector::class,
                MailCollector::class,
                NotificationsCollector::class,
                HttpCollector::class,
                EventsCollector::class,
            ],
            $collectors,
        );
    }

    public function test_it_keeps_the_data_within_one_megabyte(): void
    {
        // Arrange

        $service = resolve(InsightsService::class);
        $service->start(self::ID);

        foreach (range(1, 500) as $i) {
            event(new QueryExecuted('select '.str_repeat('column, ', 600), [], 1.0, DB::connection()));
        }

        // Act

        $service->flush();

        $size = filesize(base_path('vendor/sunchayn/nimbus/storage/insight/'.self::ID.'.json'));

        $insight = resolve(InsightStore::class)->pull(self::ID);

        // Assert

        $this->assertLessThanOrEqual(InsightsService::MAX_BYTES, $size);
        $this->assertTrue($insight['truncated']);
        $this->assertGreaterThan(0, $insight['dropped']['queries']);
        $this->assertNotEmpty($insight['queries']);
    }

    public function test_it_leaves_small_data_untouched(): void
    {
        // Arrange

        $query = ['sql' => 'select 1', 'bindings' => [], 'time_ms' => 1.0, 'offset_ms' => 0.0, 'connection' => 'testing'];

        $service = resolve(InsightsService::class, [
            'collectors' => [$this->makeCollector(new QueriesResult([$query, $query, $query]))],
        ]);

        $service->start(self::ID);

        // Act

        $service->flush();

        $insight = resolve(InsightStore::class)->pull(self::ID);

        // Assert

        $this->assertCount(3, $insight['queries']);
        $this->assertSame(0, $insight['dropped']['queries']);
        $this->assertFalse($insight['truncated']);
    }

    public function test_it_drops_the_exception_when_halving_the_lists_is_not_enough(): void
    {
        // Arrange

        $exception = [
            'class' => 'E',
            'message' => str_repeat('a', InsightsService::MAX_BYTES + 1),
            'file' => 'f',
            'line' => 1,
            'trace' => [],
        ];

        $service = resolve(InsightsService::class, [
            'collectors' => [$this->makeCollector(new ExceptionResult($exception))],
        ]);

        $service->start(self::ID);

        // Act

        $service->flush();

        $insight = resolve(InsightStore::class)->pull(self::ID);

        // Assert

        $this->assertNull($insight['exception']);
    }

    private function makeCollector(InsightCollectorResult $result): InsightCollectorContract
    {
        return new class($result) implements InsightCollectorContract
        {
            public bool $started = false;

            public function __construct(private readonly InsightCollectorResult $result) {}

            public function start(): void
            {
                $this->started = true;
            }

            public function collect(): InsightCollectorResult
            {
                return $this->result;
            }
        };
    }
}
