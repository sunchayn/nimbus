<?php

namespace Sunchayn\Nimbus\Modules\Relay\DataTransferObjects;

use Illuminate\Support\Collection;
use Sunchayn\Nimbus\Http\Api\Relay\NimbusRelayRequest;
use Sunchayn\Nimbus\Modules\Config\Enums\EnvironmentIdEnum;
use Sunchayn\Nimbus\Modules\Magic\Listeners\ResetRateLimiterListener;
use Sunchayn\Nimbus\Modules\Magic\Listeners\SandboxModeListener;
use Sunchayn\Nimbus\Modules\Relay\Services\Authorization\DataTransferObjects\AuthorizationCredentials;
use Sunchayn\Nimbus\Modules\Relay\Services\Authorization\Enums\AuthorizationTypeEnum;
use Symfony\Component\HttpFoundation\ParameterBag;

readonly class RequestRelayData
{
    public const DEFAULT_TIMEOUT = 30;

    public const MAX_TIMEOUT = 300;

    /**
     * @param  array<string, string>  $headers
     * @param  array<string, mixed>|string  $body
     * @param  array<string, string|list<string>>  $queryParameters
     */
    public function __construct(
        public string $method,
        public string $endpoint,
        public AuthorizationCredentials $authorization,
        public array $headers,
        public array|string $body,
        public ParameterBag $cookies,
        public array $queryParameters = [],
        public bool $transactionMode = false,
        public string $environment = EnvironmentIdEnum::Current->value,
        public bool $isCurrentHost = true,
        public bool $followRedirects = true,
        public int $timeout = self::DEFAULT_TIMEOUT,
        public ?bool $verifySsl = null,
        public ?string $timeTravel = null,
        public bool $resetLimiter = false,
        public string $sandbox = '',
    ) {}

    public static function fromRelayApiRequest(NimbusRelayRequest $nimbusRelayRequest): self
    {
        /**
         * @var array{
         *     headers?: array<array-key, array{key: string, value: string}>,
         *     method: string,
         *     endpoint: string,
         *     authorization?: array{type: string, value?: string|array{username: string, password: string}|null, guard?: string|null, abilities?: string|null},
         *     body: mixed,
         *     options?: array{follow_redirects?: bool, timeout?: int, verify_ssl?: bool},
         * } $data
         **/
        $data = $nimbusRelayRequest->validated();

        // Header names are case-insensitive, so names are lowercased first and repeated names are joined.
        /** @var Collection<string, string> $headers */
        $headers = collect($data['headers'] ?? [])
            ->groupBy(fn (array $header): string => strtolower($header['key']))
            ->map(fn (Collection $group): string => $group->pluck('value')->implode(', '));

        $headers->when(
            ! $headers->has('accept'),
            fn () => $headers->put('accept', 'application/json'),
        );

        $headers->when(
            ! $headers->has('user-agent') && $nimbusRelayRequest->userAgent() !== null,
            fn () => $headers->put('user-agent', (string) $nimbusRelayRequest->userAgent()),
        );

        [
            'endpoint' => $endpoint,
            'queryParameters' => $queryParameters,
        ] = self::extractAndRemoveQueryParametersFromEndpoint($data['endpoint']);

        $transactionMode = $nimbusRelayRequest->header('X-Nimbus-Transaction-Mode') === '1';

        return new self(
            method: strtolower($data['method']),
            endpoint: $endpoint,
            authorization: array_key_exists('authorization', $data)
                ? new AuthorizationCredentials(
                    type: AuthorizationTypeEnum::from($data['authorization']['type']),
                    value: $data['authorization']['value'] ?? null,
                    guard: $data['authorization']['guard'] ?? null,
                    abilities: self::parseAbilities($data['authorization']['abilities'] ?? null),
                )
                : AuthorizationCredentials::none(),
            headers: $headers->all(),
            body: $nimbusRelayRequest->getBody(),
            cookies: $nimbusRelayRequest->cookies,
            queryParameters: $queryParameters,
            transactionMode: $transactionMode,
            environment: $nimbusRelayRequest->environmentId(),
            isCurrentHost: $nimbusRelayRequest->isCurrentHost(),
            followRedirects: $data['options']['follow_redirects'] ?? true,
            timeout: $data['options']['timeout'] ?? self::DEFAULT_TIMEOUT,
            verifySsl: $data['options']['verify_ssl'] ?? null,
            timeTravel: self::timeTravelFrom($nimbusRelayRequest),
            resetLimiter: $nimbusRelayRequest->header(ResetRateLimiterListener::HEADER) === '1',
            sandbox: (string) $nimbusRelayRequest->header(SandboxModeListener::HEADER),
        );
    }

    private static function timeTravelFrom(NimbusRelayRequest $nimbusRelayRequest): ?string
    {
        $value = $nimbusRelayRequest->header('X-Nimbus-Time-Travel');

        return is_string($value) && trim($value) !== '' ? trim($value) : null;
    }

    /**
     * Abilities arrive as one comma separated string.
     *
     * @return list<string>
     */
    private static function parseAbilities(mixed $abilities): array
    {
        if (! is_string($abilities)) {
            return [];
        }

        return array_values(array_filter(array_map(trim(...), explode(',', $abilities)), fn (string $ability): bool => $ability !== ''));
    }

    /**
     * @return array{endpoint: string, queryParameters: array<string, string|list<string>>}
     */
    private static function extractAndRemoveQueryParametersFromEndpoint(string $endpoint): array
    {
        $urlParts = parse_url($endpoint);

        if (! $urlParts) {
            return [
                'endpoint' => $endpoint,
                'queryParameters' => [],
            ];
        }

        $cleanEndpoint = (array_key_exists('scheme', $urlParts) ? $urlParts['scheme'].'://' : '')
            .(array_key_exists('host', $urlParts) ? $urlParts['host'] : '')
            .(array_key_exists('port', $urlParts) ? ':'.$urlParts['port'] : '')
            .(array_key_exists('path', $urlParts) ? $urlParts['path'] : '');

        $queryParameters = array_key_exists('query', $urlParts)
            ? self::parseQueryString($urlParts['query'])
            : [];

        return [
            'endpoint' => $cleanEndpoint,
            'queryParameters' => $queryParameters,
        ];
    }

    /**
     * Splits on the first equals sign only, so values such as base64 padding survive.
     * The result is decoded because the HTTP client encodes the parameters again.
     *
     * @return array<string, string|list<string>>
     */
    private static function parseQueryString(string $query): array
    {
        $parameters = [];

        foreach (explode('&', $query) as $pair) {
            if ($pair === '') {
                continue;
            }

            [$key, $value] = array_pad(explode('=', $pair, 2), 2, '');

            $key = urldecode($key);
            $value = urldecode($value);

            if (str_ends_with($key, '[]')) {
                $name = substr($key, 0, -2);

                $parameters[$name] = [...(array) ($parameters[$name] ?? []), $value];

                continue;
            }

            $parameters[$key] = $value;
        }

        return $parameters;
    }
}
