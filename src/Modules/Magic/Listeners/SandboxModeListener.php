<?php

declare(strict_types=1);

namespace Sunchayn\Nimbus\Modules\Magic\Listeners;

use Closure;
use Illuminate\Foundation\Http\Events\RequestHandled;
use Illuminate\Routing\Events\RouteMatched;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Str;
use Sunchayn\Nimbus\Modules\Magic\Services\Insights\InsightsService;
use Symfony\Component\Mime\Address;
use Symfony\Component\Mime\Email;

/**
 * Fakes outside effects for one request and hands what was captured to the insight collector.
 *
 * Event faking is opt in because it also disables model events.
 */
class SandboxModeListener
{
    public const HEADER = 'X-Nimbus-Sandbox';

    public const OPTIONS = ['mail', 'notifications', 'queue', 'events'];

    public const MAILER = 'nimbus-sandbox';

    private const MAX_ITEMS = 20;

    private const BODY_LIMIT = 10_000;

    public function handle(RouteMatched $routeMatched): void
    {
        $options = array_intersect(self::OPTIONS, array_map(trim(...), explode(',', (string) $routeMatched->request->header(self::HEADER))));

        if ($options === []) {
            return;
        }

        /** @var array<string, Closure(): list<mixed>> $captures */
        $captures = [];

        if (in_array('mail', $options, true)) {
            // The array transport keeps the rendered message, which a mail fake does not.
            config(['mail.mailers.'.self::MAILER => ['transport' => 'array'], 'mail.default' => self::MAILER]);
            Mail::purge();

            $captures['mail'] = $this->capturedMail(...);
        }

        if (in_array('notifications', $options, true)) {
            $notificationFake = Notification::fake();

            $captures['notifications'] = fn (): array => $this->capturedNotifications($notificationFake->sentNotifications());
        }

        if (in_array('queue', $options, true)) {
            $queueFake = Queue::fake();

            $captures['jobs'] = fn (): array => $this->capturedJobs($queueFake->pushedJobs());
        }

        if (in_array('events', $options, true)) {
            // The request handled event is kept so the transaction and insight listeners can finish.
            Event::fakeExcept([RequestHandled::class]);
        }

        app(InsightsService::class)->captureSandbox(
            fn (): array => array_map(fn (Closure $capture): array => $capture(), $captures),
        );
    }

    /**
     * @return list<array{subject: string, to: list<string>, body: string}>
     */
    private function capturedMail(): array
    {
        $messages = [];

        foreach (Mail::mailer(self::MAILER)->getSymfonyTransport()->messages() as $sentMessage) { // @phpstan-ignore method.notFound
            $email = $sentMessage->getOriginalMessage();

            if (! $email instanceof Email) {
                continue;
            }

            $body = $email->getHtmlBody() ?? $email->getTextBody();

            $messages[] = [
                'subject' => (string) $email->getSubject(),
                'to' => array_values(array_map(fn (Address $address): string => $address->getAddress(), $email->getTo())),
                'body' => mb_substr(is_string($body) ? $body : '', 0, self::BODY_LIMIT),
            ];

            if (count($messages) >= self::MAX_ITEMS) {
                break;
            }
        }

        return $messages;
    }

    /**
     * @param  array<string, array<array-key, array<string, list<array{channels?: list<string>}>>>>  $sent
     * @return list<array{notification: string, notifiable: string, channels: list<string>}>
     */
    private function capturedNotifications(array $sent): array
    {
        $captured = [];

        foreach ($sent as $notifiable => $byId) {
            foreach ($byId as $id => $byNotification) {
                foreach ($byNotification as $notification => $records) {
                    foreach ($records as $record) {
                        $captured[] = [
                            'notification' => $this->className($notification),
                            'notifiable' => $this->className($notifiable).($id === '' ? '' : '#'.$id),
                            'channels' => $record['channels'] ?? [],
                        ];
                    }
                }
            }
        }

        return array_slice($captured, 0, self::MAX_ITEMS);
    }

    /**
     * @param  array<string, list<array{queue?: string|null}>>  $pushed
     * @return list<array{job: string, queue: string}>
     */
    private function capturedJobs(array $pushed): array
    {
        $captured = [];

        foreach ($pushed as $job => $records) {
            foreach ($records as $record) {
                $captured[] = ['job' => $this->className($job), 'queue' => $record['queue'] ?? 'default'];
            }
        }

        return array_slice($captured, 0, self::MAX_ITEMS);
    }

    /**
     * Anonymous classes carry their file path in the name, which is noise.
     */
    private function className(string $class): string
    {
        return str_contains($class, '@anonymous') ? Str::before($class, '@anonymous').'@anonymous' : $class;
    }
}
