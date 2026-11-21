<?php

declare(strict_types=1);

namespace Sunchayn\Nimbus\Tests\Concerns;

use Sunchayn\Nimbus\Modules\Mcp\Server\NimbusMcpServer;
use Sunchayn\Nimbus\Tests\Support\McpResult;
use Sunchayn\Nimbus\Tests\Support\McpStdioTransport;

/**
 * Talks to the MCP server in memory, the way a client does over stdio.
 */
trait InteractsWithMcpServer
{
    private const REQUEST_ID = 2;

    /**
     * @param  array<string, mixed>  $arguments
     */
    protected function callTool(string $name, array $arguments = []): McpResult
    {
        return $this->sendMcpRequest(
            method: 'tools/call',
            params: [
                'name' => $name,
                'arguments' => $this->toJsonObject(values: $arguments),
            ],
        );
    }

    protected function listTools(): McpResult
    {
        return $this->sendMcpRequest(method: 'tools/list');
    }

    protected function readResource(string $uri): McpResult
    {
        return $this->sendMcpRequest(
            method: 'resources/read',
            params: ['uri' => $uri],
        );
    }

    /**
     * @param  array<string, string>  $arguments
     */
    protected function getPrompt(string $name, array $arguments = []): McpResult
    {
        return $this->sendMcpRequest(
            method: 'prompts/get',
            params: [
                'name' => $name,
                'arguments' => (object) $arguments,
            ],
        );
    }

    /**
     * @param  array<string, mixed>  $params
     */
    protected function sendMcpRequest(string $method, array $params = []): McpResult
    {
        $input = fopen('php://temp', 'w+');
        $output = fopen('php://temp', 'w+');

        $messages = $this->listMessages(
            method: $method,
            params: $params,
        );

        $this->writeMessages(
            stream: $input,
            messages: $messages,
        );

        $stdioTransport = new McpStdioTransport(
            input: $input,
            output: $output,
        );

        resolve(NimbusMcpServer::class)->build()->run($stdioTransport);

        return $this->readResponse(
            stream: $output,
            id: self::REQUEST_ID,
        );
    }

    /**
     * The handshake a client sends first, then the request itself.
     *
     * @param  array<string, mixed>  $params
     * @return list<array<string, mixed>>
     */
    private function listMessages(string $method, array $params): array
    {
        return [
            [
                'jsonrpc' => '2.0',
                'id' => 1,
                'method' => 'initialize',
                'params' => [
                    'protocolVersion' => '2025-06-18',
                    'capabilities' => (object) [],
                    'clientInfo' => ['name' => 'phpunit', 'version' => '1.0.0'],
                ],
            ],
            [
                'jsonrpc' => '2.0',
                'method' => 'notifications/initialized',
            ],
            [
                'jsonrpc' => '2.0',
                'id' => self::REQUEST_ID,
                'method' => $method,
                'params' => (object) $params,
            ],
        ];
    }

    /**
     * @param  resource  $stream
     * @param  list<array<string, mixed>>  $messages
     */
    private function writeMessages($stream, array $messages): void
    {
        foreach ($messages as $message) {
            fwrite($stream, json_encode($message)."\n");
        }

        rewind($stream);
    }

    /**
     * @param  resource  $stream
     */
    private function readResponse($stream, int $id): McpResult
    {
        rewind($stream);

        $responses = collect(explode("\n", trim((string) stream_get_contents($stream))))
            ->map(static fn (string $line): array => json_decode($line, true))
            ->keyBy('id');

        return new McpResult($responses->get($id));
    }

    /**
     * A client sends `{}` for an empty object, but PHP encodes an empty array as `[]`.
     *
     * @param  array<string, mixed>  $values
     */
    private function toJsonObject(array $values): object
    {
        $objectValues = array_map(
            fn (mixed $value): mixed => match (true) {
                $value === [] => (object) [],
                is_array($value) && ! array_is_list($value) => $this->toJsonObject(values: $value),
                default => $value,
            },
            $values,
        );

        return (object) $objectValues;
    }
}
