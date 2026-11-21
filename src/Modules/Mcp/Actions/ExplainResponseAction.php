<?php

declare(strict_types=1);

namespace Sunchayn\Nimbus\Modules\Mcp\Actions;

use Sunchayn\Nimbus\Modules\Mcp\DataTransferObjects\ResponseExplanation;
use Sunchayn\Nimbus\Modules\Mcp\Exceptions\McpToolException;
use Sunchayn\Nimbus\Modules\Mcp\Services\SentRequestStore;

/**
 * Explains a request sent earlier, from its status, its body, and what Laravel did while it ran.
 *
 * @final
 */
class ExplainResponseAction
{
    public function __construct(private readonly SentRequestStore $sentRequestStore) {}

    /**
     * @return array<string, mixed>
     *
     * @throws McpToolException
     */
    public function execute(string $sentRequestId): array
    {
        $sentRequest = $this->sentRequestStore->find($sentRequestId) ?? throw McpToolException::becauseSentRequestIsNotFound(sentRequestId: $sentRequestId);

        /** @var array<string, mixed> $response */
        $response = $sentRequest['response'];
        $status = (int) $response['status'];

        /** @var array<string, mixed>|null $insight */
        $insight = $sentRequest['insight'] ?? null;

        $explanation = $this->explain(
            status: $status,
            body: $response['body'] ?? null,
            insight: $insight,
        );

        return [
            'sentRequestId' => $sentRequestId,
            'route' => $sentRequest['route'] ?? null,
            'status' => $status,
            'likelyCause' => $explanation->cause,
            'evidence' => [
                ...$explanation->evidence,
                ...$this->listMissingInsightNote(insight: $insight),
                ...$this->listLogs(insight: $insight),
                ...$this->listQueries(insight: $insight),
            ],
        ];
    }

    /**
     * @param  array<string, mixed>|null  $insight
     */
    private function explain(int $status, mixed $body, ?array $insight): ResponseExplanation
    {
        $exceptionExplanation = $this->explainException(insight: $insight);

        if ($exceptionExplanation instanceof ResponseExplanation) {
            return $exceptionExplanation;
        }

        $statusExplanation = $this->explainStatus(status: $status, body: $body, insight: $insight);

        if ($statusExplanation instanceof ResponseExplanation) {
            return $statusExplanation;
        }

        if ($status >= 500) {
            return new ResponseExplanation(cause: 'The server failed and no exception was recorded.');
        }

        return new ResponseExplanation(cause: 'The request was handled without an error.');
    }

    /**
     * @param  array<string, mixed>|null  $insight
     */
    private function explainException(?array $insight): ?ResponseExplanation
    {
        $exception = $insight['exception'] ?? null;

        if (! is_array($exception)) {
            return null;
        }

        $trace = array_map(
            static fn (string $frame): string => "at {$frame}",
            $exception['trace'],
        );

        return new ResponseExplanation(
            cause: sprintf('An exception was thrown, `%s`: %s', $exception['class'], $exception['message']),
            evidence: [
                sprintf('Thrown at %s:%d.', $exception['file'], $exception['line']),
                ...$trace,
            ],
        );
    }

    /**
     * @param  array<string, mixed>|null  $insight
     */
    private function explainStatus(int $status, mixed $body, ?array $insight): ?ResponseExplanation
    {
        return match ($status) {
            401 => new ResponseExplanation(cause: 'The request is not authenticated. Send it with `actor` set to a user key.'),
            403 => $this->explainForbidden(insight: $insight),
            404 => $this->explainNotFound(body: $body),
            405 => new ResponseExplanation(cause: 'The route does not accept this method.'),
            419 => new ResponseExplanation(cause: 'The CSRF token check failed.'),
            422 => $this->explainValidation(body: $body),
            429 => new ResponseExplanation(cause: 'The request was throttled.'),
            default => null,
        };
    }

    /**
     * @param  array<string, mixed>|null  $insight
     */
    private function explainForbidden(?array $insight): ResponseExplanation
    {
        $deniedChecks = array_filter(
            (array) ($insight['authChecks'] ?? []),
            static fn (array $check): bool => $check['result'] === false,
        );

        if ($deniedChecks === []) {
            return new ResponseExplanation(
                cause: 'The request was forbidden by middleware or by an authorization rule that was not recorded.',
            );
        }

        $evidence = collect($deniedChecks)
            ->map(static fn (array $check): string => sprintf(
                'Denied `%s`%s.',
                $check['ability'],
                $check['user'] === null ? ' for a guest' : " for user {$check['user']}",
            ))
            ->values()
            ->all();

        return new ResponseExplanation(
            cause: 'An authorization check denied the user.',
            evidence: $evidence,
        );
    }

    private function explainNotFound(mixed $body): ResponseExplanation
    {
        $message = is_array($body) && is_string($body['message'] ?? null) ? $body['message'] : null;

        if ($message === null) {
            return new ResponseExplanation(cause: 'No route matched, or a bound model was not found.');
        }

        return new ResponseExplanation(cause: "No route matched, or a bound model was not found. Message: {$message}");
    }

    private function explainValidation(mixed $body): ResponseExplanation
    {
        $errors = is_array($body) && is_array($body['errors'] ?? null) ? $body['errors'] : [];

        if ($errors === []) {
            return new ResponseExplanation(cause: 'Validation failed.');
        }

        $evidence = collect($errors)
            ->map(static fn (mixed $messages, int|string $field): string => sprintf(
                '`%s`: %s',
                $field,
                implode(' ', array_map(strval(...), (array) $messages)),
            ))
            ->values()
            ->all();

        $fields = collect(array_keys($errors))
            ->map(static fn (int|string $field): string => "`{$field}`")
            ->implode(', ');

        return new ResponseExplanation(
            cause: "Validation failed on {$fields}.",
            evidence: $evidence,
        );
    }

    /**
     * @param  array<string, mixed>|null  $insight
     * @return array<int, string>
     */
    private function listMissingInsightNote(?array $insight): array
    {
        if ($insight !== null) {
            return [];
        }

        return ['No insight was recorded. It exists for the current host only, when Nimbus runs in the application.'];
    }

    /**
     * @param  array<string, mixed>|null  $insight
     * @return array<int, string>
     */
    private function listLogs(?array $insight): array
    {
        return collect((array) ($insight['logs'] ?? []))
            ->take(5)
            ->map(static fn (array $log): string => sprintf('Log %s: %s', $log['level'], $log['message']))
            ->values()
            ->all();
    }

    /**
     * @param  array<string, mixed>|null  $insight
     * @return array<int, string>
     */
    private function listQueries(?array $insight): array
    {
        if ($insight === null) {
            return [];
        }

        $slowestQuery = collect((array) ($insight['queries'] ?? []))->sortByDesc('timeMs')->first();

        return collect([
            sprintf('%d queries ran.', (int) $insight['queryCount']),
            $slowestQuery === null ? null : sprintf('Slowest query, %s ms: %s', $slowestQuery['timeMs'], $slowestQuery['sql']),
        ])
            ->filter()
            ->values()
            ->all();
    }
}
