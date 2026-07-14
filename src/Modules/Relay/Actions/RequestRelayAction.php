<?php

namespace Sunchayn\Nimbus\Modules\Relay\Actions;

use Carbon\CarbonImmutable;
use GuzzleHttp\Cookie\SetCookie;
use Illuminate\Container\Container;
use Illuminate\Cookie\CookieValuePrefix;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Http;
use RuntimeException;
use Sunchayn\Nimbus\Modules\Relay\Authorization\Handlers\AuthorizationHandlerFactory;
use Sunchayn\Nimbus\Modules\Relay\DataTransferObjects\RelayedRequestResponseData;
use Sunchayn\Nimbus\Modules\Relay\DataTransferObjects\RequestRelayData;
use Sunchayn\Nimbus\Modules\Relay\Responses\DumpAndDieResponse;
use Sunchayn\Nimbus\Modules\Relay\ValueObjects\PrintableResponseBody;
use Sunchayn\Nimbus\Modules\Relay\ValueObjects\ResponseCookieValueObject;
use Symfony\Component\HttpFoundation\Response as SymfonyResponse;

class RequestRelayAction
{
    public const DEFAULT_CONTENT_TYPE = 'application/json';

    public const MICROSECONDS_TO_MILLISECONDS = 1_000_000;

    public const NON_STANDARD_STATUS_CODES = [
        419 => 'Method Not Allowed',
        DumpAndDieResponse::DUMP_AND_DIE_STATUS_CODE => 'dd()',
    ];

    public function __construct(
        private readonly Container $container,
        private readonly AuthorizationHandlerFactory $authorizationHandlerFactory,
    ) {}

    public function execute(RequestRelayData $requestRelayData): RelayedRequestResponseData
    {
        $pendingRequest = $this->prepareRequest($requestRelayData);

        $authorizationHandler = $this
            ->authorizationHandlerFactory
            ->create($requestRelayData->authorization);

        $pendingRequest = $authorizationHandler->authorize($pendingRequest);

        $start = hrtime(true);

        $response = $this->sendPendingRequest($pendingRequest, $requestRelayData);

        $durationInMs = $this->calculateDuration($start);

        return new RelayedRequestResponseData(
            statusCode: $response->status(),
            statusText: $this->getStatusTextFromCode($response->status()),
            body: PrintableResponseBody::fromResponse($response),
            headers: $response->headers(),
            durationMs: $durationInMs,
            timestamp: CarbonImmutable::now()->getTimestamp(),
            cookies: $this->processCookies($response->toPsrResponse()->getHeader('Set-Cookie')),
        );
    }

    private function prepareRequest(RequestRelayData $requestRelayData): PendingRequest
    {
        $contentType = $requestRelayData->headers['content-type'] ?? self::DEFAULT_CONTENT_TYPE;

        $queryParameters = $requestRelayData->queryParameters;
        $requestBody = $requestRelayData->body;

        if (is_array($requestBody) && in_array($requestRelayData->method, ['get', 'head'])) {
            $queryParameters = array_merge(
                $queryParameters,
                $requestBody,
            );

            $requestBody = [];
        }

        $headers = $requestRelayData->headers;

        // Add transaction mode header if enabled
        if ($requestRelayData->transactionMode) {
            $headers['x-nimbus-transaction-mode'] = '1';
        }

        // SSL verification is disabled to support development environments with self-signed certificates.
        return Http::withoutVerifying()
            ->withHeaders($headers)
            ->withQueryParameters($queryParameters)
            ->when(
                $requestBody !== [],
                function (PendingRequest $pendingRequest) use ($requestBody, $contentType) {
                    $body = match (true) {
                        is_string($requestBody) => $requestBody,
                        default => json_encode($requestBody) ?: throw new RuntimeException('Cannot parse body.'),
                    };

                    return $pendingRequest->withBody(
                        $body,
                        contentType: $contentType,
                    );
                },
            );
    }

    /**
     * Calculates request duration in milliseconds with precision formatting.
     *
     * Uses high-resolution time for accurate microsecond measurements,
     * formatted to 2 decimal places for readability.
     */
    private function calculateDuration(int $startTime): float
    {
        return (float) number_format(
            (hrtime(true) - $startTime) / self::MICROSECONDS_TO_MILLISECONDS,
            2,
            thousands_separator: ''
        );
    }

    /**
     * Processes Set-Cookie headers into structured cookie objects.
     *
     * Cookies are URL-decoded and prefixed with Laravel's encryption key
     * to match the application's cookie handling behavior.
     *
     * @param  string[]  $setCookieHeaders
     * @return ResponseCookieValueObject[]
     */
    private function processCookies(array $setCookieHeaders): array
    {
        return Arr::map(
            $setCookieHeaders,
            function (string $cookieString): ResponseCookieValueObject {
                $setCookie = SetCookie::fromString($cookieString);

                return new ResponseCookieValueObject(
                    key: $setCookie->getName(),
                    rawValue: urldecode($setCookie->getValue() ?? ''),
                    prefix: CookieValuePrefix::create(
                        $setCookie->getName(),
                        $this->container->get('encrypter')->getKey()
                    ),
                );
            },
        );
    }

    /**
     * Maps HTTP status codes to human-readable status text.
     *
     * Includes Laravel-specific status codes like 419 (Method Not Allowed)
     * that aren't part of the standard HTTP specification.
     */
    private function getStatusTextFromCode(int $statusCode): string
    {
        $statusCodeToTextMapping = SymfonyResponse::$statusTexts + self::NON_STANDARD_STATUS_CODES;

        return $statusCodeToTextMapping[$statusCode] ?? 'Non-standard Status Code.';
    }

    private function sendPendingRequest(PendingRequest $pendingRequest, RequestRelayData $requestRelayData): Response
    {
        $response = $pendingRequest->send(
            method: $requestRelayData->method,
            url: $requestRelayData->endpoint,
        );

        if (! $response->serverError()) {
            return $response;
        }

        if (! str_contains($response->body(), 'Sfdump = window.Sfdump')) {
            return $response;
        }

        return new DumpAndDieResponse($response->toPsrResponse());
    }
}
