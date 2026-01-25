<?php

namespace Sunchayn\Nimbus\Tests\App\Modules\Relay\DataTransferObjects;

use Generator;
use Mockery;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Sunchayn\Nimbus\Http\Api\Relay\NimbusRelayRequest;
use Sunchayn\Nimbus\Modules\Relay\Authorization\AuthorizationTypeEnum;
use Sunchayn\Nimbus\Modules\Relay\DataTransferObjects\RequestRelayData;
use Symfony\Component\HttpFoundation\InputBag;

#[CoversClass(RequestRelayData::class)]
class RequestRelayDataUnitTest extends TestCase
{
    #[DataProvider('relayRequestDataDataProvider')]
    public function test_it_creates_instance_from_api_request(
        string $endpoint,
        string $expectedEndpoint,
        array $expectedParameters,
        array|string $body,
    ): void {
        // Arrange

        $mockRequest = Mockery::mock(NimbusRelayRequest::class);

        $mockRequest->shouldReceive('userAgent')->andReturn('::dummy_user_agent::');

        $mockRequest->shouldReceive('host')->andReturn('::dummy_host::');

        $mockCookies = new InputBag;

        $mockRequest->cookies = $mockCookies;

        $stubAuthorizationType = AuthorizationTypeEnum::Bearer;

        // Anticipate

        $mockRequest
            ->shouldReceive('validated')
            ->andReturn(
                [
                    'method' => $method = 'POST',
                    'endpoint' => $endpoint,
                    'authorization' => [
                        'type' => $stubAuthorizationType->value,
                        'value' => $authorizationValue = 'foobar',
                    ],
                    'headers' => [
                        ['key' => 'Content-Type', 'value' => 'application/json'],
                        ['key' => 'X-Custom-Header', 'value' => '::value::'],
                    ],
                    'body' => $body,
                ],
            );

        $mockRequest->shouldReceive('getBody')->andReturn($body);
        $mockRequest->shouldReceive('header')->with('X-Nimbus-Transaction-Mode')->andReturnNull();

        // Act

        $result = RequestRelayData::fromRelayApiRequest($mockRequest);

        // Assert

        $this->assertEquals(strtolower($method), $result->method);

        $this->assertEquals($expectedEndpoint, $result->endpoint);

        $this->assertEquals($stubAuthorizationType, $result->authorization->type);

        $this->assertEquals($authorizationValue, $result->authorization->value);

        $this->assertEquals(
            [
                'accept' => 'application/json',
                'content-type' => 'application/json',
                'x-custom-header' => '::value::',
                'user-agent' => '::dummy_user_agent::',
            ],
            $result->headers);

        $this->assertEquals($body, $result->body);

        $this->assertSame($mockCookies, $result->cookies);

        $this->assertEquals(
            $expectedParameters,
            $result->queryParameters,
        );

        $this->assertFalse($result->transactionMode);
    }

    public function test_it_extracts_transaction_mode_from_header(): void
    {
        // Arrange

        $mockRequest = Mockery::mock(NimbusRelayRequest::class);

        $mockRequest->shouldReceive('userAgent')->andReturn('::dummy_user_agent::');

        $mockRequest->shouldReceive('host')->andReturn('::dummy_host::');

        $mockRequest->cookies = new InputBag;

        // Anticipate

        $mockRequest
            ->shouldReceive('validated')
            ->andReturn(
                [
                    'method' => 'POST',
                    'endpoint' => '/api/test',
                    'authorization' => [
                        'type' => AuthorizationTypeEnum::Bearer->value,
                        'value' => 'foobar',
                    ],
                    'body' => [],
                ],
            );

        $mockRequest->shouldReceive('getBody')->andReturn([]);
        $mockRequest->shouldReceive('header')->with('X-Nimbus-Transaction-Mode')->andReturn('1');

        // Act

        $result = RequestRelayData::fromRelayApiRequest($mockRequest);

        // Assert

        $this->assertTrue($result->transactionMode);
    }

    public static function relayRequestDataDataProvider(): Generator
    {
        yield 'simple path without params' => [
            'endpoint' => '/api/test',
            'expectedEndpoint' => '/api/test',
            'expectedParameters' => [],
            'body' => ['test' => 'data'],
        ];

        yield 'simple path with single param' => [
            'endpoint' => '/api/test?parameter-1=value',
            'expectedEndpoint' => '/api/test',
            'expectedParameters' => ['parameter-1' => 'value'],
            'body' => ['test' => 'data'],
        ];

        yield 'absolute URL without params' => [
            'endpoint' => 'https://127.0.0.1/api/test',
            'expectedEndpoint' => 'https://127.0.0.1/api/test',
            'expectedParameters' => [],
            'body' => ['test' => 'data'],
        ];

        yield 'absolute URL with multiple params including broken' => [
            'endpoint' => 'https://127.0.0.1/api/test?key=1&key-2=&broken',
            'expectedEndpoint' => 'https://127.0.0.1/api/test',
            'expectedParameters' => ['key' => '1', 'key-2' => ''],
            'body' => ['test' => 'data'],
        ];

        yield 'absolute URL with multiple valid params' => [
            'endpoint' => 'https://127.0.0.1/api/test?key=value&key-2=value-2',
            'expectedEndpoint' => 'https://127.0.0.1/api/test',
            'expectedParameters' => ['key' => 'value', 'key-2' => 'value-2'],
            'body' => ['test' => 'data'],
        ];

        yield 'absolute URL with port and param' => [
            'endpoint' => 'https://127.0.0.1:8000/api/test?key=value',
            'expectedEndpoint' => 'https://127.0.0.1:8000/api/test',
            'expectedParameters' => ['key' => 'value'],
            'body' => ['test' => 'data'],
        ];

        yield 'invalid URL with port and param' => [
            'endpoint' => 'http://:80?key=value',
            'expectedEndpoint' => 'http://:80?key=value', // parse_url failed, return as is.
            'expectedParameters' => [],
            'body' => ['test' => 'data'],
        ];

        yield 'plain text body' => [
            'endpoint' => '/api/test',
            'expectedEndpoint' => '/api/test',
            'expectedParameters' => [],
            'body' => 'plain text content',
        ];
    }
}
