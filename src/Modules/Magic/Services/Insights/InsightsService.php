<?php

declare(strict_types=1);

namespace Sunchayn\Nimbus\Modules\Magic\Services\Insights;

use Closure;
use Illuminate\Contracts\Events\Dispatcher;
use Illuminate\Foundation\Http\Events\RequestHandled;
use Sunchayn\Nimbus\Modules\Magic\DataTransferObjects\InsightData;
use Sunchayn\Nimbus\Modules\Magic\Services\Insights\Collectors\InsightCollectorContract;
use Sunchayn\Nimbus\Modules\Magic\Services\Insights\Support\InsightClock;

/**
 * Runs every collector strategy during one request and writes the result to the insight store when the request is handled.
 *
 * @phpstan-import-type Sandbox from InsightData
 */
class InsightsService
{
    public const MAX_BYTES = 1_048_576;

    public const COLLECTORS_TAG = 'nimbus.insight-collectors';

    private string $id = '';

    private ?Closure $sandboxCapture = null;

    /**
     * @param  iterable<InsightCollectorContract>  $collectors
     */
    public function __construct(
        private readonly InsightStore $insightStore,
        private readonly Dispatcher $dispatcher,
        private readonly InsightClock $insightClock,
        private readonly iterable $collectors,
    ) {}

    /**
     * @param  Closure(): Sandbox  $capture
     */
    public function captureSandbox(Closure $capture): void
    {
        $this->sandboxCapture = $capture;
    }

    public function start(string $id): void
    {
        $this->id = $id;
        $this->insightClock->start();

        foreach ($this->collectors as $collector) {
            $collector->start();
        }

        $this->dispatcher->listen(
            events: RequestHandled::class,
            listener: fn () => $this->flush(),
        );
    }

    public function flush(): void
    {
        $results = [];

        foreach ($this->collectors as $collector) {
            $results[] = $collector->collect();
        }

        $insightData = InsightData::fromResults(
            results: $results,
            peakMemory: memory_get_peak_usage(true),
            durationMs: $this->insightClock->elapsedMs(),
            sandbox: $this->sandboxCapture instanceof Closure ? ($this->sandboxCapture)() : [],
        );

        $this->insightStore->write(
            id: $this->id,
            json: $this->encodeWithinLimit($insightData),
        );
    }

    /**
     * Lists are cut in half until the data fits in the size limit.
     */
    private function encodeWithinLimit(InsightData $data): string
    {
        $json = $this->encode($data);

        while (strlen($json) > self::MAX_BYTES && ($smaller = $data->halved()) instanceof InsightData) {
            $data = $smaller;
            $json = $this->encode($data);
        }

        return strlen($json) > self::MAX_BYTES
            ? $this->encode($data->withoutException())
            : $json;
    }

    private function encode(InsightData $data): string
    {
        return (string) json_encode($data->toArray(), JSON_INVALID_UTF8_SUBSTITUTE | JSON_PARTIAL_OUTPUT_ON_ERROR);
    }
}
