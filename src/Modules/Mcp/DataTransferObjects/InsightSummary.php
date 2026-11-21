<?php

declare(strict_types=1);

namespace Sunchayn\Nimbus\Modules\Mcp\DataTransferObjects;

use Sunchayn\Nimbus\Modules\Magic\DataTransferObjects\InsightData;

/**
 * Shrinks an insight to what an agent needs, so it fits in a result and in the sent request store.
 * Query bindings, code excerpts, and low level logs are left out.
 */
final readonly class InsightSummary
{
    private const MAX_QUERIES = 20;

    private const MAX_FRAMES = 5;

    private const LOG_LEVELS = ['warning', 'error', 'critical', 'alert', 'emergency'];

    /**
     * @param  array<string, mixed>  $entries
     */
    public function __construct(private array $entries) {}

    public static function fromInsight(InsightData $insightData): self
    {
        return new self([
            'exception' => self::compactException(exception: $insightData->exception->exception),
            'authChecks' => $insightData->gates->gates,
            'queryCount' => $insightData->queries->count(),
            'queries' => self::compactQueries(queries: $insightData->queries->entries),
            'logs' => self::listSevereLogs(logs: $insightData->logs->entries),
            'effects' => self::compactEffects(insightData: $insightData),
            'durationMs' => $insightData->durationMs,
            'truncated' => $insightData->truncated,
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return $this->entries;
    }

    /**
     * @return array{exception: string|null, deniedChecks: int, queryCount: int}
     */
    public function toHeadline(): array
    {
        $exception = $this->entries['exception'] ?? null;

        $deniedChecks = collect((array) ($this->entries['authChecks'] ?? []))
            ->filter(static fn (array $check): bool => $check['result'] === false)
            ->count();

        return [
            'exception' => is_array($exception) ? "{$exception['class']}: {$exception['message']}" : null,
            'deniedChecks' => $deniedChecks,
            'queryCount' => (int) ($this->entries['queryCount'] ?? 0),
        ];
    }

    /**
     * @param  array<string, mixed>|null  $exception
     * @return array<string, mixed>|null
     */
    private static function compactException(?array $exception): ?array
    {
        if ($exception === null) {
            return null;
        }

        $trace = collect((array) $exception['trace'])
            ->take(self::MAX_FRAMES)
            ->map(static fn (array $frame): string => "{$frame['file']}:{$frame['line']} {$frame['call']}")
            ->all();

        return [
            'class' => $exception['class'],
            'message' => $exception['message'],
            'file' => $exception['file'],
            'line' => $exception['line'],
            'trace' => $trace,
        ];
    }

    /**
     * @param  array<int, array<string, mixed>>  $queries
     * @return array<int, array<string, mixed>>
     */
    private static function compactQueries(array $queries): array
    {
        return collect($queries)
            ->take(self::MAX_QUERIES)
            ->map(static fn (array $query): array => [
                'sql' => $query['sql'],
                'timeMs' => $query['time_ms'],
                'connection' => $query['connection'],
            ])
            ->values()
            ->all();
    }

    /**
     * @param  array<int, array<string, mixed>>  $logs
     * @return array<int, array<string, mixed>>
     */
    private static function listSevereLogs(array $logs): array
    {
        return collect($logs)
            ->filter(static fn (array $log): bool => in_array(strtolower($log['level']), self::LOG_LEVELS, true))
            ->values()
            ->all();
    }

    /**
     * @return array<string, array<int, string>>
     */
    private static function compactEffects(InsightData $insightData): array
    {
        return array_filter([
            'events' => array_column($insightData->events->entries, 'name'),
            'jobs' => array_column($insightData->jobs->entries, 'job'),
            'mail' => array_column($insightData->mail->entries, 'subject'),
            'notifications' => array_column($insightData->notifications->entries, 'notification'),
            'http' => collect($insightData->http->entries)
                ->map(static fn (array $entry): string => "{$entry['method']} {$entry['url']}")
                ->all(),
            'cache' => collect($insightData->cache->entries)
                ->map(static fn (array $entry): string => "{$entry['operation']} {$entry['key']}")
                ->all(),
        ]);
    }
}
