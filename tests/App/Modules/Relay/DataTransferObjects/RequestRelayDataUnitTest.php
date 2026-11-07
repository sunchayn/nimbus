<?php

namespace Sunchayn\Nimbus\Tests\App\Modules\Relay\DataTransferObjects;

use Generator;
use Mockery;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Sunchayn\Nimbus\Http\Api\Relay\NimbusRelayRequest;
use Sunchayn\Nimbus\Modules\Relay\DataTransferObjects\RequestRelayData;
use Sunchayn\Nimbus\Modules\Relay\Services\Authorization\Enums\AuthorizationTypeEnum;
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
        $mockRequest->shouldReceive('header')->with('X-Nimbus-Time-Travel')->andReturnNull();
        $mockRequest->shouldReceive('header')->with('X-Nimbus-Reset-Limiter')->andReturnNull();
        $mockRequest->shouldReceive('environmentId')->andReturn('current');
        $mockRequest->shouldReceive('isCurrentHost')->andReturnTrue();

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

    public function test_it_reads_request_options(): void
    {
        // Arrange

        $mockRequest = Mockery::mock(NimbusRelayRequest::class);

        $mockRequest->shouldReceive('userAgent')->andReturnNull();
        $mockRequest->cookies = new InputBag;

        // Anticipate

        $mockRequest
            ->shouldReceive('validated')
            ->andReturn([
                'method' => 'GET',
                'endpoint' => '/api/test',
                'body' => [],
                'options' => ['follow_redirects' => false, 'timeout' => 5, 'verify_ssl' => true],
            ]);

        $mockRequest->shouldReceive('getBody')->andReturn([]);
        $mockRequest->shouldReceive('header')->with('X-Nimbus-Transaction-Mode')->andReturnNull();
        $mockRequest->shouldReceive('header')->with('X-Nimbus-Time-Travel')->andReturnNull();
        $mockRequest->shouldReceive('header')->with('X-Nimbus-Reset-Limiter')->andReturnNull();
        $mockRequest->shouldReceive('environmentId')->andReturn('current');
        $mockRequest->shouldReceive('isCurrentHost')->andReturnTrue();

        // Act

        $result = RequestRelayData::fromRelayApiRequest($mockRequest);

        // Assert

        $this->assertFalse($result->followRedirects);
        $this->assertSame(5, $result->timeout);
        $this->assertTrue($result->verifySsl);
    }

    public function test_it_keeps_user_headers_regardless_of_name_case(): void
    {
        // Arrange

        $mockRequest = Mockery::mock(NimbusRelayRequest::class);

        $mockRequest->shouldReceive('userAgent')->andReturn('::browser_user_agent::');

        $mockRequest->cookies = new InputBag;

        // Anticipate

        $mockRequest
            ->shouldReceive('validated')
            ->andReturn(
                [
                    'method' => 'GET',
                    'endpoint' => '/api/test',
                    'headers' => [
                        ['key' => 'accept', 'value' => 'text/csv'],
                        ['key' => 'User-Agent', 'value' => '::custom_agent::'],
                        ['key' => 'X-Tag', 'value' => 'a'],
                        ['key' => 'x-tag', 'value' => 'b'],
                    ],
                    'body' => [],
                ],
            );

        $mockRequest->shouldReceive('getBody')->andReturn([]);
        $mockRequest->shouldReceive('header')->with('X-Nimbus-Transaction-Mode')->andReturnNull();
        $mockRequest->shouldReceive('header')->with('X-Nimbus-Time-Travel')->andReturnNull();
        $mockRequest->shouldReceive('header')->with('X-Nimbus-Reset-Limiter')->andReturnNull();
        $mockRequest->shouldReceive('environmentId')->andReturn('current');
        $mockRequest->shouldReceive('isCurrentHost')->andReturnTrue();

        // Act

        $result = RequestRelayData::fromRelayApiRequest($mockRequest);

        // Assert

        $this->assertSame(
            [
                'accept' => 'text/csv',
                'user-agent' => '::custom_agent::',
                'x-tag' => 'a, b',
            ],
            $result->headers,
        );
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
        $mockRequest->shouldReceive('header')->with('X-Nimbus-Time-Travel')->andReturnNull();
        $mockRequest->shouldReceive('header')->with('X-Nimbus-Reset-Limiter')->andReturnNull();
        $mockRequest->shouldReceive('environmentId')->andReturn('staging');
        $mockRequest->shouldReceive('isCurrentHost')->andReturnFalse();

        // Act

        $result = RequestRelayData::fromRelayApiRequest($mockRequest);

        // Assert

        $this->assertTrue($result->transactionMode);
    }

    public function test_it_reads_the_time_travel_header(): void
    {
        // Arrange

        $mockRequest = Mockery::mock(NimbusRelayRequest::class);
        $mockRequest->shouldReceive('validated')->andReturn([
            'method' => 'GET',
            'endpoint' => '/api/test',
            'body' => [],
        ]);
        $mockRequest->shouldReceive('userAgent')->andReturnNull();
        $mockRequest->shouldReceive('getBody')->andReturn([]);
        $mockRequest->shouldReceive('header')->with('X-Nimbus-Transaction-Mode')->andReturnNull();
        $mockRequest->shouldReceive('header')->with('X-Nimbus-Time-Travel')->andReturn(' +31 days ');
        $mockRequest->shouldReceive('header')->with('X-Nimbus-Reset-Limiter')->andReturnNull();
        $mockRequest->shouldReceive('environmentId')->andReturn('current');
        $mockRequest->shouldReceive('isCurrentHost')->andReturnTrue();
        $mockRequest->cookies = new InputBag;

        // Act

        $result = RequestRelayData::fromRelayApiRequest($mockRequest);

        // Assert

        $this->assertSame('+31 days', $result->timeTravel);
    }

    public function test_it_splits_the_token_abilities(): void
    {
        // Arrange

        $mockRequest = Mockery::mock(NimbusRelayRequest::class);
        $mockRequest->shouldReceive('validated')->andReturn([
            'method' => 'GET',
            'endpoint' => '/api/test',
            'authorization' => [
                'type' => AuthorizationTypeEnum::Token->value,
                'value' => '4',
                'abilities' => ' posts:read, ,posts:write ',
            ],
            'body' => [],
        ]);
        $mockRequest->shouldReceive('userAgent')->andReturnNull();
        $mockRequest->shouldReceive('getBody')->andReturn([]);
        $mockRequest->shouldReceive('header')->with('X-Nimbus-Transaction-Mode')->andReturnNull();
        $mockRequest->shouldReceive('header')->with('X-Nimbus-Time-Travel')->andReturn(null);
        $mockRequest->shouldReceive('header')->with('X-Nimbus-Reset-Limiter')->andReturnNull();
        $mockRequest->shouldReceive('environmentId')->andReturn('current');
        $mockRequest->shouldReceive('isCurrentHost')->andReturnTrue();
        $mockRequest->cookies = new InputBag;

        // Act

        $result = RequestRelayData::fromRelayApiRequest($mockRequest);

        // Assert

        $this->assertSame(['posts:read', 'posts:write'], $result->authorization->abilities);
    }

    public function test_it_reads_the_reset_limiter_header(): void
    {
        // Arrange

        $mockRequest = Mockery::mock(NimbusRelayRequest::class);
        $mockRequest->cookies = new InputBag;

        $mockRequest->shouldReceive('validated')->andReturn(['method' => 'GET', 'endpoint' => '/api/test', 'body' => []]);
        $mockRequest->shouldReceive('userAgent')->andReturnNull();
        $mockRequest->shouldReceive('getBody')->andReturn([]);
        $mockRequest->shouldReceive('header')->with('X-Nimbus-Transaction-Mode')->andReturnNull();
        $mockRequest->shouldReceive('header')->with('X-Nimbus-Time-Travel')->andReturnNull();
        $mockRequest->shouldReceive('header')->with('X-Nimbus-Reset-Limiter')->andReturn('1');
        $mockRequest->shouldReceive('environmentId')->andReturn('current');
        $mockRequest->shouldReceive('isCurrentHost')->andReturnTrue();

        // Act

        $result = RequestRelayData::fromRelayApiRequest($mockRequest);

        // Assert

        $this->assertTrue($result->resetLimiter);
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

        yield 'absolute URL with multiple params including a flag' => [
            'endpoint' => 'https://127.0.0.1/api/test?key=1&key-2=&flag',
            'expectedEndpoint' => 'https://127.0.0.1/api/test',
            'expectedParameters' => ['key' => '1', 'key-2' => '', 'flag' => ''],
            'body' => ['test' => 'data'],
        ];

        yield 'value with base64 padding' => [
            'endpoint' => '/api/test?token=abc==',
            'expectedEndpoint' => '/api/test',
            'expectedParameters' => ['token' => 'abc=='],
            'body' => ['test' => 'data'],
        ];

        yield 'encoded value is decoded once' => [
            'endpoint' => '/api/test?q=hello%20world&r=a%26b&s=a+b',
            'expectedEndpoint' => '/api/test',
            'expectedParameters' => ['q' => 'hello world', 'r' => 'a&b', 's' => 'a b'],
            'body' => ['test' => 'data'],
        ];

        yield 'repeated bracket keys keep every value' => [
            'endpoint' => '/api/test?a[]=1&a[]=2&b=3',
            'expectedEndpoint' => '/api/test',
            'expectedParameters' => ['a' => ['1', '2'], 'b' => '3'],
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
