<?php

declare(strict_types=1);

namespace Sunchayn\Nimbus\Modules\Mcp\Actions;

use Illuminate\Contracts\Http\Kernel;
use Illuminate\Http\Request;
use Sunchayn\Nimbus\Modules\Magic\Actions\PullInsightAction;
use Sunchayn\Nimbus\Modules\Magic\Enums\NimbusHeaderEnum;
use Sunchayn\Nimbus\Modules\Mcp\DataTransferObjects\ExecutionRequestData;
use Sunchayn\Nimbus\Modules\Mcp\DataTransferObjects\ExecutionResultData;
use Symfony\Component\HttpFoundation\Response;

/**
 * Runs one request through the HTTP kernel of the current process.
 * It is the work of the child process of `ProcessRequestExecutor`.
 *
 * @final
 */
class HandleKernelRequestAction
{
    public function __construct(
        private readonly Kernel $kernel,
        private readonly PullInsightAction $pullInsightAction,
    ) {}

    public function execute(ExecutionRequestData $executionRequestData): ExecutionResultData
    {
        $request = $this->makeRequest(executionRequestData: $executionRequestData);

        $insightId = PullInsightAction::newId();

        $request->headers->set(PullInsightAction::HEADER, $insightId);

        $start = hrtime(true);

        $response = $this->kernel->handle($request);

        $durationMs = round((hrtime(true) - $start) / 1_000_000, 2);

        // Terminating callbacks roll back the transaction mode, so this must run before the result is returned.
        $this->kernel->terminate($request, $response);

        return new ExecutionResultData(
            status: $response->getStatusCode(),
            headers: $this->listResponseHeaders(response: $response),
            body: (string) $response->getContent(),
            durationMs: $durationMs,
            insight: $this->pullInsightAction->execute($insightId),
        );
    }

    private function makeRequest(ExecutionRequestData $executionRequestData): Request
    {
        $method = strtoupper($executionRequestData->method);

        [$query, $body] = $this->placeFields(
            method: $method,
            query: $executionRequestData->query,
            body: $executionRequestData->body,
        );

        $content = $this->encodeContent(body: $body);

        $uri = $this->buildUri(path: $executionRequestData->path, query: $query);

        $request = Request::create(
            uri: $uri,
            method: $method,
            content: $content,
        );

        if ($content !== null && ! array_key_exists('content-type', $executionRequestData->headers)) {
            $request->headers->set('Content-Type', 'application/json');
        }

        foreach ($executionRequestData->headers as $name => $value) {
            $request->headers->set($name, $value);
        }

        if ($executionRequestData->transactionMode) {
            $request->headers->set(NimbusHeaderEnum::TransactionMode->value, '1');
        }

        return $request;
    }

    /**
     * @param  array<string, mixed>  $query
     */
    private function buildUri(string $path, array $query): string
    {
        if ($query === []) {
            return $path;
        }

        return $path.'?'.http_build_query($query);
    }

    /**
     * @param  array<string, string|list<string>>  $query
     * @param  array<array-key, mixed>|string  $body
     * @return array{array<string, mixed>, array<array-key, mixed>|string}
     */
    private function placeFields(string $method, array $query, array|string $body): array
    {
        // A read request has no body, so its fields travel in the query string like in the UI.
        if (in_array($method, ['GET', 'HEAD'], true) && is_array($body)) {
            return [[...$query, ...$body], []];
        }

        return [$query, $body];
    }

    /**
     * @param  array<array-key, mixed>|string  $body
     */
    private function encodeContent(array|string $body): ?string
    {
        if (is_string($body)) {
            return $body;
        }

        if ($body === []) {
            return null;
        }

        return (string) json_encode($body);
    }

    /**
     * @return array<string, string>
     */
    private function listResponseHeaders(Response $response): array
    {
        $headers = [];

        foreach ($response->headers->all() as $name => $values) {
            $headers[strtolower((string) $name)] = implode(', ', (array) $values);
        }

        return $headers;
    }
}
