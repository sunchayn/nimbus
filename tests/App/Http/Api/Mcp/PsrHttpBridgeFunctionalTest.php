<?php

declare(strict_types=1);

namespace Sunchayn\Nimbus\Tests\App\Http\Api\Mcp;

use GuzzleHttp\Psr7\HttpFactory;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Mcp\Server\Transport\CallbackStream;
use PHPUnit\Framework\Attributes\CoversClass;
use Sunchayn\Nimbus\Http\Api\Mcp\PsrHttpBridge;
use Sunchayn\Nimbus\Tests\TestCase;
use Symfony\Component\HttpFoundation\StreamedResponse;

#[CoversClass(PsrHttpBridge::class)]
class PsrHttpBridgeFunctionalTest extends TestCase
{
    public function test_it_converts_a_laravel_request_to_psr_7(): void
    {
        // Arrange

        $request = Request::create(
            uri: 'http://localhost/nimbus/mcp?draft=1',
            method: 'POST',
            server: ['CONTENT_TYPE' => 'application/json', 'HTTP_X_CUSTOM' => 'yes'],
            content: '{"jsonrpc":"2.0"}',
        );

        $bridge = resolve(PsrHttpBridge::class);

        // Act

        $psrRequest = $bridge->toPsrRequest(request: $request);

        // Assert

        $this->assertSame(
            'POST',
            $psrRequest->getMethod(),
        );

        $this->assertSame(
            'http://localhost/nimbus/mcp?draft=1',
            (string) $psrRequest->getUri(),
        );

        $this->assertSame(
            'yes',
            $psrRequest->getHeaderLine('X-Custom'),
        );

        $this->assertSame(
            '{"jsonrpc":"2.0"}',
            (string) $psrRequest->getBody(),
        );

        $this->assertSame(
            ['draft' => '1'],
            $psrRequest->getQueryParams(),
        );
    }

    public function test_it_converts_a_sized_psr_7_response_to_a_plain_response(): void
    {
        // Arrange

        $psrResponse = (new HttpFactory)
            ->createResponse(202)
            ->withHeader('Content-Type', 'application/json')
            ->withBody((new HttpFactory)->createStream('{"ok":true}'));

        $bridge = resolve(PsrHttpBridge::class);

        // Act

        $response = $bridge->toLaravelResponse(psrResponse: $psrResponse);

        // Assert

        $this->assertInstanceOf(
            Response::class,
            $response,
        );

        $this->assertSame(
            202,
            $response->getStatusCode(),
        );

        $this->assertSame(
            'application/json',
            $response->headers->get('Content-Type'),
        );

        $this->assertSame(
            '{"ok":true}',
            $response->getContent(),
        );
    }

    public function test_it_streams_a_psr_7_body_of_unknown_size(): void
    {
        // Arrange

        $stream = new CallbackStream(function (): void {
            echo "event: message\n\n";
        });

        $psrResponse = (new HttpFactory)
            ->createResponse(200)
            ->withHeader('Content-Type', 'text/event-stream')
            ->withBody($stream);

        $bridge = resolve(PsrHttpBridge::class);

        // Act

        $response = $bridge->toLaravelResponse(psrResponse: $psrResponse);

        ob_start();

        $response->sendContent();

        $streamedContent = ob_get_clean();

        // Assert

        $this->assertInstanceOf(
            StreamedResponse::class,
            $response,
        );

        $this->assertSame(
            'text/event-stream',
            $response->headers->get('Content-Type'),
        );

        $this->assertSame(
            "event: message\n\n",
            $streamedContent,
        );
    }
}
