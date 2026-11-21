<?php

declare(strict_types=1);

namespace Sunchayn\Nimbus\Http\Api\Mcp;

use Http\Discovery\Psr17Factory;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\StreamInterface;
use Symfony\Component\HttpFoundation\Response as SymfonyResponse;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Converts between Laravel messages and the PSR-7 messages the MCP SDK works with.
 */
class PsrHttpBridge
{
    private const READ_CHUNK_BYTES = 8192;

    private readonly Psr17Factory $psr17Factory;

    public function __construct()
    {
        $this->psr17Factory = new Psr17Factory;
    }

    public function toPsrRequest(Request $request): ServerRequestInterface
    {
        $psrRequest = $this->psr17Factory->createServerRequest(
            method: $request->getMethod(),
            uri: $request->fullUrl(),
            serverParams: $request->server->all(),
        );

        foreach ($request->headers->all() as $name => $values) {
            $stringValues = array_filter($values, is_string(...));

            $psrRequest = $psrRequest->withHeader($name, $stringValues);
        }

        $body = $this->psr17Factory->createStream($request->getContent());

        return $psrRequest
            ->withBody($body)
            ->withQueryParams($request->query->all());
    }

    /**
     * A body of unknown size is a stream that writes its events to the output when it is read.
     * It must reach the client as a stream, with the headers sent first.
     */
    public function toLaravelResponse(ResponseInterface $psrResponse): SymfonyResponse
    {
        $body = $psrResponse->getBody();

        if ($body->getSize() !== null) {
            return new Response(
                content: (string) $body,
                status: $psrResponse->getStatusCode(),
                headers: $psrResponse->getHeaders(),
            );
        }

        return new StreamedResponse(
            fn () => $this->echoBody(body: $body),
            status: $psrResponse->getStatusCode(),
            headers: $psrResponse->getHeaders(),
        );
    }

    private function echoBody(StreamInterface $body): void
    {
        while (! $body->eof()) {
            echo $body->read(self::READ_CHUNK_BYTES);
        }
    }
}
