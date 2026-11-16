<?php

declare(strict_types=1);

namespace Sunchayn\Nimbus\Tests\App\Modules\Relay\DataTransferObjects;

use GuzzleHttp\Psr7\Request;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\StreamInterface;
use Sunchayn\Nimbus\Modules\Relay\DataTransferObjects\SentRequestData;

#[CoversClass(SentRequestData::class)]
class SentRequestDataUnitTest extends TestCase
{
    public function test_it_describes_the_request_that_was_sent(): void
    {
        // Arrange

        $request = new Request(
            'POST',
            'https://example.com/api/users?page=2',
            ['Accept' => ['application/json', 'text/plain'], 'X-Token' => 'abc'],
            '{"name":"Jane"}',
            '1.1',
        );

        // Act

        $sent = SentRequestData::fromPsrRequest($request);

        // Assert

        $this->assertSame('POST', $sent->method);
        $this->assertSame('https://example.com/api/users?page=2', $sent->url);
        $this->assertSame('1.1', $sent->protocolVersion);
        $this->assertSame('{"name":"Jane"}', $sent->body);
        $this->assertFalse($sent->bodyTruncated);

        $this->assertContains(['key' => 'Accept', 'value' => 'application/json'], $sent->headers);
        $this->assertContains(['key' => 'Accept', 'value' => 'text/plain'], $sent->headers);
        $this->assertContains(['key' => 'X-Token', 'value' => 'abc'], $sent->headers);
    }

    public function test_it_truncates_a_body_larger_than_the_limit(): void
    {
        // Arrange

        $body = str_repeat('a', SentRequestData::MAX_BODY_BYTES + 10);

        $request = new Request('POST', 'https://example.com', [], $body);

        // Act

        $sent = SentRequestData::fromPsrRequest($request);

        // Assert

        $this->assertSame(SentRequestData::MAX_BODY_BYTES, strlen($sent->body));
        $this->assertTrue($sent->bodyTruncated);
    }

    public function test_it_returns_an_empty_body_when_the_stream_cannot_be_read(): void
    {
        // Arrange

        $stream = $this->createStub(StreamInterface::class);
        $stream->method('rewind')->willThrowException(new \RuntimeException('Not seekable.'));

        $request = (new Request('POST', 'https://example.com'))->withBody($stream);

        // Act

        $sent = SentRequestData::fromPsrRequest($request);

        // Assert

        $this->assertSame('', $sent->body);
        $this->assertFalse($sent->bodyTruncated);
    }

    public function test_it_turns_into_an_array(): void
    {
        // Arrange

        $sent = new SentRequestData(
            method: 'GET',
            url: 'https://example.com',
            protocolVersion: '2',
            headers: [['key' => 'Accept', 'value' => '*/*']],
            body: '',
            bodyTruncated: false,
        );

        // Act

        $array = $sent->toArray();

        // Assert

        $this->assertSame(
            [
                'method' => 'GET',
                'url' => 'https://example.com',
                'protocolVersion' => '2',
                'headers' => [['key' => 'Accept', 'value' => '*/*']],
                'body' => '',
                'bodyTruncated' => false,
            ],
            $array,
        );
    }
}
