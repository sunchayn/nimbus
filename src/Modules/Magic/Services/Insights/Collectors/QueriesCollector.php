<?php

namespace Sunchayn\Nimbus\Modules\Magic\Services\Insights\Collectors;

use Illuminate\Contracts\Foundation\Application;
use Illuminate\Database\Events\QueryExecuted;
use Illuminate\Support\Facades\DB;
use Sunchayn\Nimbus\Modules\Magic\Services\Insights\Results\QueriesResult;
use Sunchayn\Nimbus\Modules\Magic\Services\Insights\Support\InsightClock;
use Sunchayn\Nimbus\Modules\Magic\Services\Insights\Support\InsightValueLimiter;
use Sunchayn\Nimbus\Modules\Magic\Services\Insights\Support\SecretBindingMasker;

/**
 * @phpstan-import-type Query from QueriesResult
 */
class QueriesCollector implements InsightCollectorContract
{
    public const MAX_QUERIES = 500;

    /** @var list<Query> */
    private array $queries = [];

    private int $dropped = 0;

    public function __construct(
        private readonly Application $application,
        private readonly SecretBindingMasker $secretBindingMasker,
        private readonly InsightClock $insightClock,
        private readonly InsightValueLimiter $insightValueLimiter,
    ) {}

    public function start(): void
    {
        DB::listen(fn (QueryExecuted $query) => $this->record($query));
    }

    private function record(QueryExecuted $query): void
    {
        if (count($this->queries) >= self::MAX_QUERIES) {
            $this->dropped++;

            return;
        }

        $bindings = array_values($query->bindings);

        $this->queries[] = [
            'sql' => $query->sql,
            'bindings' => array_map(
                $this->insightValueLimiter->limit(...),
                $this->application->environment('production') ? $this->secretBindingMasker->mask($query->sql, $bindings) : $bindings,
            ),
            'time_ms' => round((float) $query->time, 2),
            // The event fires when the query ends, so the start is the end minus the duration.
            'offset_ms' => $this->insightClock->offsetMs(microtime(true) - (float) $query->time / 1000),
            'connection' => $query->connectionName,
        ];
    }

    public function collect(): QueriesResult
    {
        return new QueriesResult($this->queries, $this->dropped);
    }
}
