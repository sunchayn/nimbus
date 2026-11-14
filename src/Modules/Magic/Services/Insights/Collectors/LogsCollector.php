<?php

declare(strict_types=1);

namespace Sunchayn\Nimbus\Modules\Magic\Services\Insights\Collectors;

use Illuminate\Contracts\Events\Dispatcher;
use Illuminate\Log\Events\MessageLogged;
use Sunchayn\Nimbus\Modules\Magic\Services\Insights\Results\LogsResult;
use Sunchayn\Nimbus\Modules\Magic\Services\Insights\Support\InsightValueLimiter;

/**
 * @phpstan-import-type Log from LogsResult
 */
class LogsCollector implements InsightCollectorContract
{
    public const MAX_LOGS = 200;

    /** @var list<Log> */
    private array $logs = [];

    private int $dropped = 0;

    public function __construct(
        private readonly Dispatcher $dispatcher,
        private readonly InsightValueLimiter $insightValueLimiter,
    ) {}

    public function start(): void
    {
        $this->dispatcher->listen(MessageLogged::class, fn (MessageLogged $log) => $this->record($log));
    }

    private function record(MessageLogged $log): void
    {
        if (count($this->logs) >= self::MAX_LOGS) {
            $this->dropped++;

            return;
        }

        $this->logs[] = [
            'level' => $log->level,
            'message' => mb_substr((string) $log->message, 0, 1000),
            'context' => $this->insightValueLimiter->limitContext($log->context),
        ];
    }

    public function collect(): LogsResult
    {
        return new LogsResult($this->logs, $this->dropped);
    }
}
