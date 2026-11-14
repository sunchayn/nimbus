<?php

declare(strict_types=1);

namespace Sunchayn\Nimbus\Modules\Workspace\DataTransferObjects;

use Stringable;
use Sunchayn\Nimbus\Modules\Relay\Services\Authorization\Enums\AuthorizationTypeEnum;
use Sunchayn\Nimbus\Modules\Workspace\DataTransferObjects\Concerns\JsonStringable;
use Sunchayn\Nimbus\Modules\Workspace\Exceptions\WorkspaceException;

/**
 * One saved request, as it is stored in a request file.
 *
 * @phpstan-type Parameter array{key: string, value: string, enabled: bool}
 */
class SavedRequest implements Stringable
{
    use JsonStringable;

    public const MANIFEST_FILE_PATH = 'nimbus.json';

    public const REQUESTS_DIRECTORY = 'requests';

    public const REQUEST_SUFFIX = '.request.json';

    public const MAX_FILE_SIZE_IN_BYTES = 1_048_576;

    public const MAX_FOLDER_DEPTH = 5;

    public const METHODS = ['GET', 'POST', 'PUT', 'PATCH', 'DELETE', 'HEAD', 'OPTIONS'];

    public const BODY_TYPES = ['empty', 'json', 'plain-text'];

    /**
     * @param  list<Parameter>  $headers
     * @param  list<Parameter>  $query
     * @param  string|int|array{username: string, password: string}|null  $authorizationValue
     */
    public function __construct(
        public readonly string $id,
        public readonly string $name,
        public readonly string $method,
        public readonly string $endpoint,
        public readonly ?string $route,
        public readonly array $headers,
        public readonly array $query,
        public readonly string $bodyType,
        public readonly string $bodyContent,
        public readonly AuthorizationTypeEnum $authorizationType,
        public readonly string|int|array|null $authorizationValue,
    ) {}

    /**
     * Builds the data from the text of a request file.
     *
     * @throws WorkspaceException
     */
    public static function fromRaw(string $json): self
    {
        $decoded = json_decode($json, true);

        if (! is_array($decoded)) {
            throw WorkspaceException::unreadableRequest();
        }

        if (! is_string($decoded['id'] ?? null)) {
            throw WorkspaceException::unreadableRequest();
        }

        if (! is_string($decoded['name'] ?? null)) {
            throw WorkspaceException::unreadableRequest();
        }

        return self::fromArray($decoded);
    }

    /**
     * Builds the data from a decoded request file or a validated request payload.
     *
     * @param  array<string, mixed>  $data
     */
    public static function fromArray(array $data): self
    {
        $type = AuthorizationTypeEnum::tryFrom((string) data_get(
            $data,
            'authorization.type',
        )) ?? AuthorizationTypeEnum::None;

        $value = data_get(
            $data,
            'authorization.value',
        );

        return new self(
            id: (string) ($data['id'] ?? ''),
            name: (string) ($data['name'] ?? ''),
            method: strtoupper((string) ($data['method'] ?? '')),
            endpoint: (string) ($data['endpoint'] ?? ''),
            route: self::route($data['route'] ?? null),
            headers: self::parameters($data['headers'] ?? []),
            query: self::parameters($data['query'] ?? []),
            bodyType: (string) data_get(
                $data,
                'body.type',
                'empty',
            ),
            bodyContent: (string) data_get(
                $data,
                'body.content',
                '',
            ),
            authorizationType: $type,
            authorizationValue: self::carriesValue($type) ? self::authorizationValue($value) : null,
        );
    }

    /**
     * The file format, with a stable key order so diffs stay small.
     *
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        $authorization = ['type' => $this->authorizationType->value];

        if ($this->authorizationValue !== null) {
            $authorization['value'] = $this->authorizationValue;
        }

        return array_filter(
            [
                'id' => $this->id,
                'name' => $this->name,
                'method' => $this->method,
                'endpoint' => $this->endpoint,
                'route' => $this->route,
                'headers' => $this->headers,
                'query' => $this->query,
                'body' => $this->bodyToArray(),
                'authorization' => $authorization,
            ],
            fn (mixed $value): bool => $value !== null,
        );
    }

    public function withName(string $name): self
    {
        return self::fromArray([
            ...$this->toArray(),
            'name' => $name,
        ]);
    }

    /**
     * @return array{type: string, content?: string}
     */
    private function bodyToArray(): array
    {
        if ($this->bodyType === 'empty') {
            return ['type' => 'empty'];
        }

        return ['type' => $this->bodyType, 'content' => $this->bodyContent];
    }

    private static function route(mixed $route): ?string
    {
        if (! is_string($route)) {
            return null;
        }

        if ($route === '') {
            return null;
        }

        return $route;
    }

    private static function carriesValue(AuthorizationTypeEnum $type): bool
    {
        return in_array(
            needle: $type,
            haystack: [
                AuthorizationTypeEnum::Bearer,
                AuthorizationTypeEnum::Basic,
                AuthorizationTypeEnum::Impersonate,
                AuthorizationTypeEnum::Token,
            ],
            strict: true,
        );
    }

    /**
     * @return list<Parameter>
     */
    private static function parameters(mixed $parameters): array
    {
        if (! is_array($parameters)) {
            return [];
        }

        $normalized = [];

        foreach ($parameters as $parameter) {
            if (! is_array($parameter)) {
                continue;
            }

            $normalized[] = [
                'key' => (string) ($parameter['key'] ?? ''),
                'value' => (string) ($parameter['value'] ?? ''),
                'enabled' => (bool) ($parameter['enabled'] ?? true),
            ];
        }

        return $normalized;
    }

    /**
     * @return string|int|array{username: string, password: string}|null
     */
    private static function authorizationValue(mixed $value): string|int|array|null
    {
        if (is_array($value)) {
            return [
                'username' => (string) ($value['username'] ?? ''),
                'password' => (string) ($value['password'] ?? ''),
            ];
        }

        return is_string($value) || is_int($value) ? $value : null;
    }
}
