<?php

declare(strict_types=1);

namespace Sunchayn\Nimbus\Tests\Support;

use PHPUnit\Framework\Assert;

/**
 * One JSON-RPC response of the MCP server, with the assertions the tests need.
 */
class McpResult
{
    /**
     * @param  array<string, mixed>  $response
     */
    public function __construct(
        public readonly array $response,
    ) {}

    public function assertOk(): self
    {
        Assert::assertArrayNotHasKey(
            'error',
            $this->response,
        );

        Assert::assertNotTrue($this->response['result']['isError'] ?? false);

        return $this;
    }

    public function assertHasErrors(): self
    {
        $failed = isset($this->response['error']) || ($this->response['result']['isError'] ?? false) === true;

        Assert::assertTrue(
            $failed,
            'The response is not an error.',
        );

        return $this;
    }

    public function assertSee(string $text): self
    {
        Assert::assertStringContainsString(
            $text,
            $this->searchableText(),
        );

        return $this;
    }

    public function assertDontSee(string $text): self
    {
        Assert::assertStringNotContainsString(
            $text,
            $this->searchableText(),
        );

        return $this;
    }

    /**
     * @param  list<string>|string  $names
     */
    public function assertRegistered(array|string $names): self
    {
        Assert::assertEqualsCanonicalizing(
            (array) $names,
            array_intersect((array) $names, $this->names('tools')),
        );

        return $this;
    }

    public function assertNotRegistered(string $name): self
    {
        Assert::assertNotContains(
            $name,
            $this->names('tools'),
        );

        return $this;
    }

    /**
     * @return array<string, mixed>
     */
    public function structuredContent(): array
    {
        return $this->response['result']['structuredContent'];
    }

    /**
     * The JSON payload of a tool error, such as its code, message, and hint.
     *
     * @return array<string, mixed>
     */
    public function errorPayload(): array
    {
        return json_decode($this->response['result']['content'][0]['text'], true);
    }

    /**
     * @return list<string>
     */
    public function names(string $key): array
    {
        return array_column($this->response['result'][$key], 'name');
    }

    /**
     * The raw response plus the text the response carries, so a quote or a slash does not need escaping.
     */
    private function searchableText(): string
    {
        $texts = [
            ...array_column($this->response['result']['content'] ?? [], 'text'),
            ...array_column($this->response['result']['contents'] ?? [], 'text'),
            ...array_column(array_column($this->response['result']['messages'] ?? [], 'content'), 'text'),
            $this->response['error']['message'] ?? '',
        ];

        return json_encode($this->response, JSON_UNESCAPED_SLASHES)."\n".implode("\n", $texts);
    }
}
