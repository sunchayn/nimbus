<?php

declare(strict_types=1);

namespace Sunchayn\Nimbus\Modules\Magic\Services\Insights\Collectors;

use Illuminate\Contracts\Events\Dispatcher;
use Sunchayn\Nimbus\Modules\Magic\Services\Insights\Results\EventsResult;
use Sunchayn\Nimbus\Modules\Magic\Services\Insights\Support\InsightValueLimiter;

/**
 * Records the events of the application, and leaves out the ones the framework and its packages dispatch.
 *
 * @phpstan-import-type EventEntry from EventsResult
 *
 * @extends ListCollector<EventEntry>
 */
class EventsCollector extends ListCollector
{
    private const NOISY_EVENT_PREFIXES = [
        'Illuminate\\',
        'Sunchayn\\Nimbus\\',
        'Laravel\\',
        'Carbon\\',
        'Symfony\\',
    ];

    public function __construct(
        private readonly Dispatcher $dispatcher,
        private readonly InsightValueLimiter $insightValueLimiter,
    ) {}

    public function start(): void
    {
        $this->dispatcher->listen(
            '*',
            fn (string $event, array $payload) => $this->record($event, $payload[0] ?? null),
        );
    }

    private function record(string $event, mixed $payload): void
    {
        if (! is_object($payload) || $this->isNoisyEvent($event)) {
            return;
        }

        $this->add(['name' => (string) $this->insightValueLimiter->limit($event)]);
    }

    private function isNoisyEvent(string $event): bool
    {
        if (! str_contains($event, '\\')) {
            return true;
        }

        foreach (self::NOISY_EVENT_PREFIXES as $prefix) {
            if (str_starts_with($event, $prefix)) {
                return true;
            }
        }

        return false;
    }

    public function collect(): EventsResult
    {
        return new EventsResult($this->entries, $this->dropped);
    }
}
