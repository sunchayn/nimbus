<?php

declare(strict_types=1);

namespace Sunchayn\Nimbus\Modules\Mcp\Actions;

use Sunchayn\Nimbus\Modules\Mcp\DataTransferObjects\SendRequestData;
use Sunchayn\Nimbus\Modules\Mcp\Exceptions\McpToolException;
use Sunchayn\Nimbus\Modules\Relay\Services\Authorization\Enums\AuthorizationTypeEnum;
use Sunchayn\Nimbus\Modules\Workspace\Actions\ScanWorkspaceAction;
use Sunchayn\Nimbus\Modules\Workspace\DataTransferObjects\SavedRequest;
use Sunchayn\Nimbus\Modules\Workspace\Exceptions\WorkspaceException;

/**
 * Sends a saved request through the same safe path as nimbus_send_request.
 *
 * @final
 */
class RunSavedRequestAction
{
    private const PLACEHOLDER_PATTERN = '/\{\{\s*([^{}\s]+)\s*\}\}/';

    public function __construct(
        private readonly ScanWorkspaceAction $scanWorkspaceAction,
        private readonly SendRequestAction $sendRequestAction,
    ) {}

    /**
     * @param  array<string, mixed>  $variables
     * @return array<string, mixed>
     *
     * @throws McpToolException
     * @throws WorkspaceException
     */
    public function execute(string $savedRequestId, array $variables = []): array
    {
        $workspace = $this->scanWorkspaceAction->execute();

        $savedRequest = $workspace->entries->findRequestOrFail($savedRequestId)->savedRequest;

        $missingNames = $this->listMissingVariables(savedRequest: $savedRequest, variables: $variables);

        if ($missingNames !== []) {
            throw $this->missingVariables(names: $missingNames);
        }

        $fill = fn (string $text): string => $this->fillVariables(text: $text, variables: $variables);

        $headers = [
            ...$this->listParameters(parameters: $savedRequest->headers, fill: $fill),
            ...$this->buildAuthorizationHeaders(savedRequest: $savedRequest, fill: $fill),
        ];

        $sendRequestData = new SendRequestData(
            method: $savedRequest->method,
            uri: $fill($savedRequest->endpoint),
            query: $this->listParameters(parameters: $savedRequest->query, fill: $fill),
            headers: $headers,
            body: $this->buildBody(savedRequest: $savedRequest, fill: $fill),
            actor: $this->findActor(savedRequest: $savedRequest, fill: $fill),
        );

        return $this->sendRequestAction->execute($sendRequestData);
    }

    /**
     * The names of the `{{name}}` placeholders that have no scalar value.
     *
     * @param  array<string, mixed>  $variables
     * @return array<int, string>
     */
    private function listMissingVariables(SavedRequest $savedRequest, array $variables): array
    {
        return collect($this->listFillableTexts(savedRequest: $savedRequest))
            ->flatMap(fn (string $text): array => $this->listPlaceholderNames(text: $text))
            ->unique()
            ->reject(static fn (string $name): bool => is_scalar($variables[$name] ?? null))
            ->values()
            ->all();
    }

    /**
     * Every text that goes through the variable filling.
     *
     * @return list<string>
     */
    private function listFillableTexts(SavedRequest $savedRequest): array
    {
        $parameterTexts = collect([...$savedRequest->headers, ...$savedRequest->query])
            ->filter(static fn (array $parameter): bool => $parameter['enabled'] && $parameter['key'] !== '')
            ->flatMap(static fn (array $parameter): array => [$parameter['key'], $parameter['value']])
            ->all();

        $authorizationValue = $savedRequest->authorizationValue;

        $authorizationTexts = match (true) {
            is_array($authorizationValue) => [$authorizationValue['username'], $authorizationValue['password']],
            is_scalar($authorizationValue) => [(string) $authorizationValue],
            default => [],
        };

        return [
            $savedRequest->endpoint,
            ...$parameterTexts,
            ...$authorizationTexts,
            $savedRequest->bodyContent,
        ];
    }

    /**
     * @return list<string>
     */
    private function listPlaceholderNames(string $text): array
    {
        preg_match_all(self::PLACEHOLDER_PATTERN, $text, $matches);

        return $matches[1];
    }

    /**
     * Replaces each `{{name}}` placeholder that has a scalar value and leaves the others as they are.
     *
     * @param  array<string, mixed>  $variables
     */
    private function fillVariables(string $text, array $variables): string
    {
        return (string) preg_replace_callback(
            self::PLACEHOLDER_PATTERN,
            static function (array $match) use ($variables): string {
                $value = $variables[$match[1]] ?? null;

                return is_scalar($value) ? (string) $value : $match[0];
            },
            $text,
        );
    }

    /**
     * @param  callable(string): string  $fill
     * @return array<string, string>
     */
    private function buildAuthorizationHeaders(SavedRequest $savedRequest, callable $fill): array
    {
        $value = $savedRequest->authorizationValue;

        return match ($savedRequest->authorizationType) {
            AuthorizationTypeEnum::Bearer => ['Authorization' => 'Bearer '.$this->fillScalar(value: $value, fill: $fill)],
            AuthorizationTypeEnum::Basic => ['Authorization' => 'Basic '.$this->encodeBasicCredentials(
                username: $fill((string) data_get($value, 'username')),
                password: $fill((string) data_get($value, 'password')),
            )],
            default => [],
        };
    }

    /**
     * @param  callable(string): string  $fill
     */
    private function findActor(SavedRequest $savedRequest, callable $fill): ?string
    {
        if ($savedRequest->authorizationType !== AuthorizationTypeEnum::Impersonate) {
            return null;
        }

        return $this->fillScalar(value: $savedRequest->authorizationValue, fill: $fill);
    }

    /**
     * @param  callable(string): string  $fill
     */
    private function fillScalar(mixed $value, callable $fill): string
    {
        $text = is_scalar($value) ? (string) $value : '';

        return $fill($text);
    }

    private function encodeBasicCredentials(string $username, string $password): string
    {
        return base64_encode("{$username}:{$password}");
    }

    /**
     * @param  array<int, string>  $names
     */
    private function missingVariables(array $names): McpToolException
    {
        return McpToolException::becauseInputIsInvalid(
            message: 'The saved request needs values for '.implode(', ', $names).'.',
            hint: 'Pass them in `variables`.',
        );
    }

    /**
     * @param  list<array{key: string, value: string, enabled: bool}>  $parameters
     * @param  callable(string): string  $fill
     * @return array<string, string>
     */
    private function listParameters(array $parameters, callable $fill): array
    {
        return collect($parameters)
            ->filter(static fn (array $parameter): bool => $parameter['enabled'] && $parameter['key'] !== '')
            ->mapWithKeys(static fn (array $parameter): array => [$fill($parameter['key']) => $fill($parameter['value'])])
            ->all();
    }

    /**
     * @param  callable(string): string  $fill
     * @return array<array-key, mixed>|string
     *
     * @throws McpToolException
     */
    private function buildBody(SavedRequest $savedRequest, callable $fill): array|string
    {
        if ($savedRequest->bodyType === 'empty' || $savedRequest->bodyContent === '') {
            return [];
        }

        $content = $fill($savedRequest->bodyContent);

        if ($savedRequest->bodyType === 'plain-text') {
            return $content;
        }

        $decoded = json_decode($content, true);

        if (! is_array($decoded)) {
            throw McpToolException::becauseInputIsInvalid(
                message: 'The saved JSON body is not valid after the variables are filled.',
                hint: 'Check the variable values.',
            );
        }

        return $decoded;
    }
}
