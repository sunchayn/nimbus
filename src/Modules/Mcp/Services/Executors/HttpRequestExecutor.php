<?php

declare(strict_types=1);

namespace Sunchayn\Nimbus\Modules\Mcp\Services\Executors;

use Sunchayn\Nimbus\Modules\Config\ActiveApplicationResolver;
use Sunchayn\Nimbus\Modules\Config\DataTransferObjects\EnvironmentData;
use Sunchayn\Nimbus\Modules\Config\Enums\EnvironmentIdEnum;
use Sunchayn\Nimbus\Modules\Mcp\Contracts\RequestExecutor;
use Sunchayn\Nimbus\Modules\Mcp\DataTransferObjects\ExecutionRequestData;
use Sunchayn\Nimbus\Modules\Mcp\DataTransferObjects\ExecutionResultData;
use Sunchayn\Nimbus\Modules\Mcp\Exceptions\McpToolException;
use Sunchayn\Nimbus\Modules\Relay\Actions\RequestRelayAction;
use Sunchayn\Nimbus\Modules\Relay\DataTransferObjects\RequestRelayData;
use Sunchayn\Nimbus\Modules\Relay\Services\Authorization\DataTransferObjects\AuthorizationCredentials;
use Symfony\Component\HttpFoundation\ParameterBag;
use Throwable;

/**
 * Sends the request over HTTP with the same action the UI uses.
 * It needs a server that is already running.
 */
class HttpRequestExecutor implements RequestExecutor
{
    public function __construct(
        private readonly RequestRelayAction $requestRelayAction,
        private readonly ActiveApplicationResolver $activeApplicationResolver,
    ) {}

    public function execute(ExecutionRequestData $executionRequestData): ExecutionResultData
    {
        $requestRelayData = $this->toRelayData(executionRequest: $executionRequestData);

        try {
            $relayedResponse = $this->requestRelayAction->execute($requestRelayData);
        } catch (Throwable $throwable) {
            throw McpToolException::becauseRequestFailed(reason: $throwable->getMessage());
        }

        return new ExecutionResultData(
            status: $relayedResponse->statusCode,
            headers: $this->flattenHeaders(headers: $relayedResponse->headers),
            body: $relayedResponse->body->toPrettyJSON(),
            durationMs: $relayedResponse->durationMs,
            insight: $relayedResponse->insight,
        );
    }

    private function toRelayData(ExecutionRequestData $executionRequest): RequestRelayData
    {
        $headers = array_change_key_case($executionRequest->headers) + ['accept' => 'application/json'];

        $baseUrl = rtrim($this->getBaseUrl(environment: $executionRequest->environment), '/');

        $endpoint = $baseUrl.'/'.ltrim($executionRequest->path, '/');

        return new RequestRelayData(
            method: strtolower($executionRequest->method),
            endpoint: $endpoint,
            authorization: AuthorizationCredentials::none(),
            headers: $headers,
            body: $executionRequest->body,
            cookies: new ParameterBag,
            queryParameters: $executionRequest->query,
            transactionMode: $executionRequest->transactionMode,
            environment: $executionRequest->environment,
            isCurrentHost: $executionRequest->environment === EnvironmentIdEnum::Current->value,
        );
    }

    /**
     * @param  array<string, array<string>>  $headers
     * @return array<string, string>
     */
    private function flattenHeaders(array $headers): array
    {
        return collect($headers)
            ->mapWithKeys(static fn (array $values, string $name): array => [strtolower($name) => implode(', ', $values)])
            ->all();
    }

    private function getBaseUrl(string $environment): string
    {
        if ($environment === EnvironmentIdEnum::Current->value) {
            return $this->activeApplicationResolver->getApiBaseUrl();
        }

        $enabledEnvironment = collect($this->activeApplicationResolver->getEnvironments())
            ->first(static fn (EnvironmentData $environmentData): bool => $environmentData->id === $environment && $environmentData->enabled);

        if ($enabledEnvironment !== null) {
            return $enabledEnvironment->baseUrl;
        }

        throw McpToolException::becauseEnvironmentIsNotAllowed(
            message: sprintf('The environment `%s` does not exist or is disabled.', $environment),
            hint: 'Call nimbus_list_environments to see the environments you can use.',
        );
    }
}
