<?php

namespace Sunchayn\Nimbus\Modules\Relay\DataTransferObjects;

use Illuminate\Support\Arr;
use Illuminate\Support\Collection;
use Sunchayn\Nimbus\Http\Api\Relay\NimbusRelayRequest;
use Sunchayn\Nimbus\Modules\Relay\Authorization\AuthorizationCredentials;
use Sunchayn\Nimbus\Modules\Relay\Authorization\AuthorizationTypeEnum;
use Symfony\Component\HttpFoundation\ParameterBag;

readonly class RequestRelayData
{
    /**
     * @param  array<string, string>  $headers
     * @param  array<string, mixed>  $body
     * @param  array<string, string|null>  $queryParameters
     */
    public function __construct(
        public string $method,
        public string $endpoint,
        public AuthorizationCredentials $authorization,
        public array $headers,
        public array $body,
        public ParameterBag $cookies,
        public array $queryParameters = [],
    ) {}

    public static function fromRelayApiRequest(NimbusRelayRequest $nimbusRelayRequest): self
    {
        /**
         * @var array{
         *     headers?: array<array-key, array{key: string, value: string}>,
         *     method: string,
         *     endpoint: string,
         *     authorization?: array{type: string, value?: string|array{username: string, password: string}|null},
         *     body: mixed,
         * } $data
         **/
        $data = $nimbusRelayRequest->validated();

        /** @var Collection<string, string> $headers */
        $headers = collect($data['headers'] ?? [])
            ->pluck('value', 'key');

        $headers->when(
            ! $headers->has('Accept'),
            fn () => $headers->put('Accept', 'application/json'),
        );

        $headers->when(
            $nimbusRelayRequest->userAgent() !== null,
            fn () => $headers->put('User-Agent', (string) $nimbusRelayRequest->userAgent()),
        );

        [
            'endpoint' => $endpoint,
            'queryParameters' => $queryParameters,
        ] = self::extractAndRemoveQueryParametersFromEndpoint($data['endpoint']);

        return new self(
            method: strtolower($data['method']),
            endpoint: $endpoint,
            authorization: array_key_exists('authorization', $data)
                ? new AuthorizationCredentials(
                    type: AuthorizationTypeEnum::from($data['authorization']['type']),
                    value: $data['authorization']['value'] ?? null,
                )
                : AuthorizationCredentials::none(),
            headers: $headers->mapWithKeys(fn (mixed $value, string $key): array => [strtolower($key) => $value])->all(),
            body: $nimbusRelayRequest->getBody(),
            cookies: $nimbusRelayRequest->cookies,
            queryParameters: $queryParameters,
        );
    }

    /**
     * @return array{endpoint: string, queryParameters: array<string, string|null>}
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
            ? Arr::mapWithKeys(
                explode('&', $urlParts['query']),
                static function (string $query): array {
                    $parts = explode('=', $query);

                    if (count($parts) !== 2) {
                        return [];
                    }

                    return [$parts[0] => $parts[1]];
                },
            )
            : [];

        return [
            'endpoint' => $cleanEndpoint,
            'queryParameters' => $queryParameters,
        ];
    }
}
