<?php

declare(strict_types=1);

namespace Sunchayn\Nimbus\Modules\Magic\Services\Insights\Collectors;

use Illuminate\Cache\Events\CacheHit;
use Illuminate\Cache\Events\CacheMissed;
use Illuminate\Cache\Events\KeyForgotten;
use Illuminate\Cache\Events\KeyWritten;
use Illuminate\Contracts\Events\Dispatcher;
use Sunchayn\Nimbus\Modules\Magic\Services\Insights\Results\CacheResult;
use Sunchayn\Nimbus\Modules\Magic\Services\Insights\Support\InsightValueLimiter;

/**
 * @phpstan-import-type CacheEntry from CacheResult
 *
 * @extends ListCollector<CacheEntry>
 */
class CacheCollector extends ListCollector
{
    public function __construct(private readonly Dispatcher $dispatcher) {}

    public function start(): void
    {
        $this->dispatcher->listen(CacheHit::class, fn (CacheHit $event) => $this->record('hit', $event->key));
        $this->dispatcher->listen(CacheMissed::class, fn (CacheMissed $event) => $this->record('miss', $event->key));
        $this->dispatcher->listen(KeyWritten::class, fn (KeyWritten $event) => $this->record('write', $event->key));
        $this->dispatcher->listen(KeyForgotten::class, fn (KeyForgotten $event) => $this->record('forget', $event->key));
    }

    /**
     * @param  'hit'|'miss'|'write'|'forget'  $operation
     */
    private function record(string $operation, string $key): void
    {
        $this->add([
            'operation' => $operation,
            'key' => mb_substr($key, 0, InsightValueLimiter::LIMIT),
        ]);
    }

    public function collect(): CacheResult
    {
        return new CacheResult($this->entries, $this->dropped);
    }
}
