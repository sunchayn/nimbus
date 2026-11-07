<?php

declare(strict_types=1);

namespace Sunchayn\Nimbus\Modules\Magic\Services\Insights\Collectors;

use Illuminate\Contracts\Events\Dispatcher;
use Illuminate\Http\Client\Events\ConnectionFailed;
use Illuminate\Http\Client\Events\ResponseReceived;
use Illuminate\Http\Client\Request;
use Illuminate\Http\Client\Response;
use Sunchayn\Nimbus\Modules\Magic\Services\Insights\Results\HttpResult;
use Sunchayn\Nimbus\Modules\Magic\Services\Insights\Support\InsightClock;
use Sunchayn\Nimbus\Modules\Magic\Services\Insights\Support\InsightValueLimiter;

/**
 * Records the calls the application makes with the HTTP client, without their bodies or query strings.
 *
 * @phpstan-import-type HttpEntry from HttpResult
 *
 * @extends ListCollector<HttpEntry>
 */
class HttpCollector extends ListCollector
{
    public function __construct(
        private readonly Dispatcher $dispatcher,
        private readonly InsightClock $insightClock,
        private readonly InsightValueLimiter $insightValueLimiter,
    ) {}

    public function start(): void
    {
        $this->dispatcher->listen(
            ResponseReceived::class,
            fn (ResponseReceived $event) => $this->record($event->request, $event->response),
        );

        $this->dispatcher->listen(
            ConnectionFailed::class,
            fn (ConnectionFailed $event) => $this->record($event->request, null),
        );
    }

    private function record(Request $request, ?Response $response): void
    {
        // Fake responses and failed connections carry no transfer time, so the call has no duration.
        $seconds = $response?->transferStats?->getTransferTime();

        $this->add([
            'method' => $request->method(),
            // The query string is left out because it often carries tokens.
            'url' => (string) $this->insightValueLimiter->limit(strtok($request->url(), '?')),
            'status' => $response?->status(),
            'duration_ms' => $seconds === null ? null : round($seconds * 1000, 2),
            'offset_ms' => $seconds === null ? null : $this->insightClock->offsetMs(microtime(true) - $seconds),
        ]);
    }

    public function collect(): HttpResult
    {
        return new HttpResult($this->entries, $this->dropped);
    }
}
