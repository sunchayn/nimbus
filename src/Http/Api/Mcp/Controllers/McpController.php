<?php

declare(strict_types=1);

namespace Sunchayn\Nimbus\Http\Api\Mcp\Controllers;

use Illuminate\Http\Request;
use Mcp\Server\Transport\Http\Middleware\CorsMiddleware;
use Mcp\Server\Transport\Http\Middleware\DnsRebindingProtectionMiddleware;
use Mcp\Server\Transport\StreamableHttpTransport;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Log\LoggerInterface;
use Sunchayn\Nimbus\Http\Api\Mcp\PsrHttpBridge;
use Sunchayn\Nimbus\Modules\Mcp\Server\NimbusMcpServer;
use Sunchayn\Nimbus\Modules\Mcp\Services\McpLogger;
use Symfony\Component\HttpFoundation\Response;

class McpController
{
    /** @var list<string> */
    private const LOOPBACK_HOSTS = ['localhost', '127.0.0.1', '[::1]'];

    /** @var list<string> */
    private const LOCAL_ADDRESSES = ['127.0.0.1', '::1'];

    public function __invoke(
        Request $request,
        NimbusMcpServer $nimbusMcpServer,
        PsrHttpBridge $psrHttpBridge,
        McpLogger $mcpLogger,
    ): Response {
        $this->abortUnlessLocal(request: $request);

        $psrRequest = $psrHttpBridge->toPsrRequest(request: $request);

        $transport = $this->createTransport(
            psrRequest: $psrRequest,
            logger: $mcpLogger,
        );

        $psrResponse = $nimbusMcpServer->build()->run($transport);

        return $psrHttpBridge->toLaravelResponse(psrResponse: $psrResponse);
    }

    /**
     * Only requests from the machine itself may reach the MCP endpoint.
     */
    private function abortUnlessLocal(Request $request): void
    {
        $isLocal = in_array($request->ip(), self::LOCAL_ADDRESSES, true);

        abort_unless(
            boolean: $isLocal,
            code: 403,
            message: 'The Nimbus MCP endpoint accepts local requests only.',
        );
    }

    private function createTransport(ServerRequestInterface $psrRequest, LoggerInterface $logger): StreamableHttpTransport
    {
        return new StreamableHttpTransport(
            request: $psrRequest,
            logger: $logger,
            middleware: [
                new CorsMiddleware,
                new DnsRebindingProtectionMiddleware(allowedHosts: $this->listAllowedHosts()),
            ],
        );
    }

    /**
     * @return list<string>
     */
    private function listAllowedHosts(): array
    {
        $applicationHost = parse_url((string) config('app.url'), PHP_URL_HOST);

        if (! is_string($applicationHost)) {
            return self::LOOPBACK_HOSTS;
        }

        return [...self::LOOPBACK_HOSTS, $applicationHost];
    }
}
