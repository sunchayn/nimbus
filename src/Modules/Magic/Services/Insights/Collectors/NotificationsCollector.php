<?php

declare(strict_types=1);

namespace Sunchayn\Nimbus\Modules\Magic\Services\Insights\Collectors;

use Illuminate\Contracts\Events\Dispatcher;
use Illuminate\Notifications\Events\NotificationSending;
use Sunchayn\Nimbus\Modules\Magic\Services\Insights\Results\NotificationsResult;
use Sunchayn\Nimbus\Modules\Magic\Services\Insights\Support\InsightValueLimiter;

/**
 * @phpstan-import-type NotificationEntry from NotificationsResult
 *
 * @extends ListCollector<NotificationEntry>
 */
class NotificationsCollector extends ListCollector
{
    public function __construct(
        private readonly Dispatcher $dispatcher,
        private readonly InsightValueLimiter $insightValueLimiter,
    ) {}

    public function start(): void
    {
        $this->dispatcher->listen(NotificationSending::class, fn (NotificationSending $event) => $this->record($event));
    }

    private function record(NotificationSending $event): void
    {
        $this->add([
            'notification' => (string) $this->insightValueLimiter->limit($event->notification),
            'channel' => (string) $this->insightValueLimiter->limit($event->channel),
        ]);
    }

    public function collect(): NotificationsResult
    {
        return new NotificationsResult($this->entries, $this->dropped);
    }
}
