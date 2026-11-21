<?php

declare(strict_types=1);

namespace Sunchayn\Nimbus\Modules\Mcp\Actions;

use Illuminate\Support\Str;
use Sunchayn\Nimbus\Modules\Mcp\DataTransferObjects\SaveRequestData;
use Sunchayn\Nimbus\Modules\Mcp\Exceptions\McpToolException;
use Sunchayn\Nimbus\Modules\Workspace\Actions\SaveWorkspaceRequestAction;
use Sunchayn\Nimbus\Modules\Workspace\DataTransferObjects\SavedRequest;
use Sunchayn\Nimbus\Modules\Workspace\Exceptions\WorkspaceException;
use Sunchayn\Nimbus\Modules\Workspace\ValueObjects\FolderPath;

/**
 * Saves a new request file in the workspace. It never carries credentials.
 *
 * @final
 */
class SaveRequestAction
{
    private const SECRET_HEADERS = ['authorization', 'cookie', 'x-api-key', 'x-auth-token'];

    public function __construct(private readonly SaveWorkspaceRequestAction $saveWorkspaceRequestAction) {}

    /**
     * @return array{id: string, file: string}
     *
     * @throws McpToolException
     * @throws WorkspaceException
     */
    public function execute(SaveRequestData $data): array
    {
        $method = strtoupper($data->method);

        $this->assertValidRequest(data: $data, method: $method);

        $this->assertNoCredentials(headers: $data->headers);

        $savedRequest = SavedRequest::fromArray([
            'id' => (string) Str::uuid(),
            'name' => $data->name,
            'method' => $method,
            'endpoint' => $data->endpoint,
            'route' => $data->route,
            'headers' => $this->listParameters(values: $data->headers),
            'query' => $this->listParameters(values: $data->query),
            'body' => $this->buildBody(body: $data->body),
        ]);

        $requestFile = $this->saveWorkspaceRequestAction->execute(
            savedRequest: $savedRequest,
            hash: null,
            folder: FolderPath::from($data->folder),
        );

        return [
            'id' => $requestFile->savedRequest->id,
            'file' => $requestFile->relativeFilepath,
        ];
    }

    /**
     * @throws McpToolException
     */
    private function assertValidRequest(SaveRequestData $data, string $method): void
    {
        if (trim($data->name) !== '' && $data->endpoint !== '' && in_array($method, SavedRequest::METHODS, true)) {
            return;
        }

        throw McpToolException::becauseInputIsInvalid(
            message: 'A saved request needs a `name`, a known `method`, and an `endpoint`.',
            hint: 'Use one of '.implode(', ', SavedRequest::METHODS).'.',
        );
    }

    /**
     * @param  array<string, mixed>  $headers
     *
     * @throws McpToolException
     */
    private function assertNoCredentials(array $headers): void
    {
        foreach (array_keys($headers) as $header) {
            if (in_array(strtolower((string) $header), self::SECRET_HEADERS, true)) {
                throw McpToolException::becauseInputIsInvalid(
                    message: sprintf('The `%s` header cannot be saved.', $header),
                    hint: 'Credentials are never written to the workspace.',
                );
            }
        }
    }

    /**
     * @param  array<array-key, mixed>|string  $body
     * @return array{type: string, content?: string}
     */
    private function buildBody(array|string $body): array
    {
        $content = is_array($body) && $body !== [] ? json_encode($body, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) : $body;

        if (! is_string($content) || $content === '') {
            return ['type' => 'empty'];
        }

        return [
            'type' => is_array($body) ? 'json' : 'plain-text',
            'content' => $content,
        ];
    }

    /**
     * @param  array<string, mixed>  $values
     * @return array<int, array{key: string, value: string, enabled: bool}>
     */
    private function listParameters(array $values): array
    {
        return collect($values)
            ->map(static fn (mixed $value, int|string $key): array => [
                'key' => (string) $key,
                'value' => is_scalar($value) ? (string) $value : '',
                'enabled' => true,
            ])
            ->values()
            ->all();
    }
}
