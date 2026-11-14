<?php

namespace Sunchayn\Nimbus\Modules\Magic\DataTransferObjects;

use Sunchayn\Nimbus\Modules\Intellisense\InsightIntellisense;
use Sunchayn\Nimbus\Modules\Magic\Services\Insights\Results\CacheResult;
use Sunchayn\Nimbus\Modules\Magic\Services\Insights\Results\EventsResult;
use Sunchayn\Nimbus\Modules\Magic\Services\Insights\Results\ExceptionResult;
use Sunchayn\Nimbus\Modules\Magic\Services\Insights\Results\GatesResult;
use Sunchayn\Nimbus\Modules\Magic\Services\Insights\Results\HttpResult;
use Sunchayn\Nimbus\Modules\Magic\Services\Insights\Results\InsightCollectorResult;
use Sunchayn\Nimbus\Modules\Magic\Services\Insights\Results\InsightListResult;
use Sunchayn\Nimbus\Modules\Magic\Services\Insights\Results\JobsResult;
use Sunchayn\Nimbus\Modules\Magic\Services\Insights\Results\LogsResult;
use Sunchayn\Nimbus\Modules\Magic\Services\Insights\Results\MailResult;
use Sunchayn\Nimbus\Modules\Magic\Services\Insights\Results\NotificationsResult;
use Sunchayn\Nimbus\Modules\Magic\Services\Insights\Results\QueriesResult;

/**
 * What Laravel did while it handled one relayed request.
 *
 * @phpstan-import-type Query from QueriesResult
 * @phpstan-import-type Log from LogsResult
 * @phpstan-import-type Gate from GatesResult
 * @phpstan-import-type CacheEntry from CacheResult
 * @phpstan-import-type JobEntry from JobsResult
 * @phpstan-import-type MailEntry from MailResult
 * @phpstan-import-type NotificationEntry from NotificationsResult
 * @phpstan-import-type HttpEntry from HttpResult
 * @phpstan-import-type EventEntry from EventsResult
 * @phpstan-import-type ExceptionInfo from ExceptionResult
 *
 * @phpstan-type Dropped array{queries: int, logs: int, cache: int, jobs: int, mail: int, notifications: int, http: int, events: int}
 * @phpstan-type SandboxMail array{subject: string, to: list<string>, body: string}
 * @phpstan-type SandboxNotification array{notification: string, notifiable: string, channels: list<string>}
 * @phpstan-type SandboxJob array{job: string, queue: string}
 * @phpstan-type Sandbox array{mail?: list<SandboxMail>, notifications?: list<SandboxNotification>, jobs?: list<SandboxJob>}
 */
class InsightData
{
    /**
     * @param  Sandbox  $sandbox
     */
    public function __construct(
        public readonly QueriesResult $queries = new QueriesResult,
        public readonly LogsResult $logs = new LogsResult,
        public readonly GatesResult $gates = new GatesResult,
        public readonly CacheResult $cache = new CacheResult,
        public readonly JobsResult $jobs = new JobsResult,
        public readonly MailResult $mail = new MailResult,
        public readonly NotificationsResult $notifications = new NotificationsResult,
        public readonly HttpResult $http = new HttpResult,
        public readonly EventsResult $events = new EventsResult,
        public readonly ExceptionResult $exception = new ExceptionResult,
        public readonly int $peakMemory = 0,
        public readonly float $durationMs = 0.0,
        public readonly bool $truncated = false,
        public readonly array $sandbox = [],
    ) {}

    /**
     * Places each result in its slot. A slot without a result stays empty.
     *
     * @param  iterable<InsightCollectorResult>  $results
     * @param  Sandbox  $sandbox
     */
    public static function fromResults(iterable $results, int $peakMemory, float $durationMs, array $sandbox = []): self
    {
        return new self(
            queries: self::pick($results, QueriesResult::class),
            logs: self::pick($results, LogsResult::class),
            gates: self::pick($results, GatesResult::class),
            cache: self::pick($results, CacheResult::class),
            jobs: self::pick($results, JobsResult::class),
            mail: self::pick($results, MailResult::class),
            notifications: self::pick($results, NotificationsResult::class),
            http: self::pick($results, HttpResult::class),
            events: self::pick($results, EventsResult::class),
            exception: self::pick($results, ExceptionResult::class),
            peakMemory: $peakMemory,
            durationMs: $durationMs,
            sandbox: $sandbox,
        );
    }

    /**
     * Rebuilds the data from the array that `toArray` produced, after it went through the insight store.
     *
     * @param  array<string, mixed>  $data
     */
    public static function fromArray(array $data): self
    {
        return new self(
            queries: new QueriesResult($data['queries'] ?? [], $data['dropped']['queries'] ?? 0),
            logs: new LogsResult($data['logs'] ?? [], $data['dropped']['logs'] ?? 0),
            gates: new GatesResult($data['gates'] ?? []),
            cache: new CacheResult($data['cache'] ?? [], $data['dropped']['cache'] ?? 0),
            jobs: new JobsResult($data['jobs'] ?? [], $data['dropped']['jobs'] ?? 0),
            mail: new MailResult($data['mail'] ?? [], $data['dropped']['mail'] ?? 0),
            notifications: new NotificationsResult($data['notifications'] ?? [], $data['dropped']['notifications'] ?? 0),
            http: new HttpResult($data['http'] ?? [], $data['dropped']['http'] ?? 0),
            events: new EventsResult($data['events'] ?? [], $data['dropped']['events'] ?? 0),
            exception: new ExceptionResult($data['exception'] ?? null),
            peakMemory: (int) ($data['peak_memory'] ?? 0),
            durationMs: (float) ($data['duration_ms'] ?? 0),
            truncated: (bool) ($data['truncated'] ?? false),
            sandbox: $data['sandbox'] ?? [],
        );
    }

    /**
     * Cuts the longest list in half, or returns null when every list is empty.
     * A tie goes to the list that comes first, so queries are cut before logs.
     */
    public function halved(): ?self
    {
        $lists = $this->lists();
        $counts = array_map(fn (InsightListResult $list): int => $list->count(), $lists);

        $highest = max([0, ...$counts]);

        if ($highest === 0) {
            return null;
        }

        $longest = (string) array_search($highest, $counts, true);

        return $this->with([
            $longest => $lists[$longest]->halved(),
            'truncated' => true,
        ]);
    }

    public function withoutException(): self
    {
        return $this->with(['exception' => new ExceptionResult]);
    }

    /**
     * This is the contract with the FE.
     *
     * @see InsightIntellisense
     *
     * @return array{
     *     queries: list<Query>,
     *     logs: list<Log>,
     *     gates: list<Gate>,
     *     cache: list<CacheEntry>,
     *     jobs: list<JobEntry>,
     *     mail: list<MailEntry>,
     *     notifications: list<NotificationEntry>,
     *     http: list<HttpEntry>,
     *     events: list<EventEntry>,
     *     exception: ExceptionInfo|null,
     *     peak_memory: int,
     *     duration_ms: float,
     *     dropped: Dropped,
     *     truncated: bool,
     *     sandbox: Sandbox,
     * }
     */
    public function toArray(): array
    {
        return [
            'queries' => $this->queries->entries,
            'logs' => $this->logs->entries,
            'gates' => $this->gates->gates,
            'cache' => $this->cache->entries,
            'jobs' => $this->jobs->entries,
            'mail' => $this->mail->entries,
            'notifications' => $this->notifications->entries,
            'http' => $this->http->entries,
            'events' => $this->events->entries,
            'exception' => $this->exception->exception,
            'peak_memory' => $this->peakMemory,
            'duration_ms' => $this->durationMs,
            'dropped' => [
                'queries' => $this->queries->dropped,
                'logs' => $this->logs->dropped,
                'cache' => $this->cache->dropped,
                'jobs' => $this->jobs->dropped,
                'mail' => $this->mail->dropped,
                'notifications' => $this->notifications->dropped,
                'http' => $this->http->dropped,
                'events' => $this->events->dropped,
            ],
            'truncated' => $this->truncated,
            'sandbox' => $this->sandbox,
        ];
    }

    /**
     * The slots that hold a capped list, in the order they are cut.
     *
     * @return array<string, InsightListResult<array<string, mixed>>>
     */
    private function lists(): array
    {
        return [
            'queries' => $this->queries,
            'logs' => $this->logs,
            'cache' => $this->cache,
            'jobs' => $this->jobs,
            'mail' => $this->mail,
            'notifications' => $this->notifications,
            'http' => $this->http,
            'events' => $this->events,
        ];
    }

    /**
     * @param  array<string, mixed>  $changes  New values by slot name.
     */
    private function with(array $changes): self
    {
        return new self(...array_replace(get_object_vars($this), $changes));
    }

    /**
     * @template T of InsightCollectorResult
     *
     * @param  iterable<InsightCollectorResult>  $results
     * @param  class-string<T>  $class
     * @return T
     */
    private static function pick(iterable $results, string $class): InsightCollectorResult
    {
        foreach ($results as $result) {
            if ($result instanceof $class) {
                return $result;
            }
        }

        return new $class;
    }
}
