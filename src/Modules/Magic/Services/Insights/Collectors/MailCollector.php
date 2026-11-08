<?php

declare(strict_types=1);

namespace Sunchayn\Nimbus\Modules\Magic\Services\Insights\Collectors;

use Illuminate\Contracts\Events\Dispatcher;
use Illuminate\Mail\Events\MessageSending;
use Sunchayn\Nimbus\Modules\Magic\Listeners\SandboxModeListener;
use Sunchayn\Nimbus\Modules\Magic\Services\Insights\Results\MailResult;
use Sunchayn\Nimbus\Modules\Magic\Services\Insights\Support\InsightValueLimiter;
use Symfony\Component\Mime\Address;

/**
 * @phpstan-import-type MailEntry from MailResult
 *
 * @extends ListCollector<MailEntry>
 */
class MailCollector extends ListCollector
{
    public function __construct(
        private readonly Dispatcher $dispatcher,
        private readonly InsightValueLimiter $insightValueLimiter,
    ) {}

    public function start(): void
    {
        $this->dispatcher->listen(MessageSending::class, fn (MessageSending $event) => $this->record($event));
    }

    private function record(MessageSending $event): void
    {
        // Sandbox mode lists its mail itself, since the message is faked and not sent.
        if (config('mail.default') === SandboxModeListener::MAILER) {
            return;
        }

        $mailable = $event->data['__laravel_mailable'] ?? null;

        $this->add([
            'subject' => (string) $this->insightValueLimiter->limit((string) $event->message->getSubject()),
            'to' => array_values(array_slice(
                array_map(fn (Address $address): string => $address->getAddress(), $event->message->getTo()),
                0,
                10,
            )),
            'mailable' => is_string($mailable) ? $mailable : null,
        ]);
    }

    public function collect(): MailResult
    {
        return new MailResult($this->entries, $this->dropped);
    }
}
