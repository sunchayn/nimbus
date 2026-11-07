<?php

namespace Sunchayn\Nimbus\Tests\Integration;

use Generator;
use Illuminate\Foundation\Http\Middleware\ConvertEmptyStringsToNull;
use Illuminate\Foundation\Http\Middleware\TrimStrings;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Route;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use Sunchayn\Nimbus\Http\Api\Relay\NimbusRelayController;
use Sunchayn\Nimbus\Http\Api\Relay\NimbusRelayRequest;
use Sunchayn\Nimbus\Http\Api\Relay\RelayResponseResource;
use Sunchayn\Nimbus\Modules\Relay\Actions\RequestRelayAction;
use Sunchayn\Nimbus\Modules\Relay\DataTransferObjects\RelayedRequestResponseData;
use Sunchayn\Nimbus\Modules\Relay\DataTransferObjects\RequestRelayData;
use Sunchayn\Nimbus\Modules\Relay\Services\Authorization\Enums\AuthorizationTypeEnum;
use Sunchayn\Nimbus\Modules\Relay\ValueObjects\DownloadResponseBody;
use Sunchayn\Nimbus\Modules\Relay\ValueObjects\PrintableResponseBody;
use Sunchayn\Nimbus\Modules\Relay\ValueObjects\ResponseCookieValueObject;
use Sunchayn\Nimbus\NimbusServiceProvider;
use Sunchayn\Nimbus\Tests\TestCase;

#[CoversClass(NimbusRelayController::class)]
#[CoversClass(NimbusRelayRequest::class)]
#[CoversClass(RelayResponseResource::class)]
#[CoversClass(NimbusServiceProvider::class)]
class NimbusRelayTest extends TestCase
{
    #[DataProvider('relayRequestProvider')]
    public function test_it_relays_requests(array $payload, RelayedRequestResponseData $relayResponseStub): void
    {
        // Arrange

        $requestRelayActionMock = $this->mock(RequestRelayAction::class);

        // We want to test that the logic is resilient with or without these middlewares.
        $this->withoutMiddleware(TrimStrings::class);
        $this->withoutMiddleware(ConvertEmptyStringsToNull::class);

        // Anticipate

        $requestRelayActionMock->shouldReceive('execute')->withAnyArgs()->andReturn($relayResponseStub);

        // Act

        $response = $this->post(
            route('nimbus.api.relay'),
            $payload,
            [
                'Content-Type' => 'multipart/form-data',
                'Accept' => 'application/json',
            ]
        );

        // Assert

        $response->assertStatus(200);

        $response->assertJson([
            'statusCode' => $relayResponseStub->statusCode,
            'statusText' => $relayResponseStub->statusText,
            'body' => $relayResponseStub->body->toPrettyJSON(),
            'headers' => [
                [
                    'key' => 'header1',
                    'value' => 'value1',
                ],
            ],
            'cookies' => collect($relayResponseStub->cookies)->map(fn ($cookie) => $cookie->toArray())->all(),
            'duration' => $relayResponseStub->durationMs,
            'timestamp' => $relayResponseStub->timestamp,
        ]);
    }

    #[DataProvider('typedBodyProvider')]
    public function test_it_sends_a_typed_body_as_written(string $body, string|array $expectedBody): void
    {
        // Arrange

        $requestRelayActionMock = $this->mock(RequestRelayAction::class);

        // Anticipate

        $requestRelayActionMock
            ->shouldReceive('execute')
            ->once()
            ->withArgs(fn (RequestRelayData $data): bool => $data->body === $expectedBody)
            ->andReturn(new RelayedRequestResponseData(
                statusCode: 200,
                statusText: 'OK',
                body: new PrintableResponseBody(''),
                headers: [],
                durationMs: 1.5,
                timestamp: 1_700_000_000,
                cookies: [],
            ));

        // Act

        $response = $this->postJson(route('nimbus.api.relay'), [
            'method' => 'POST',
            'endpoint' => '/test-endpoint',
            'body' => $body,
        ]);

        // Assert

        $response->assertOk();
    }

    public static function typedBodyProvider(): Generator
    {
        yield 'false' => ['false', 'false'];
        yield 'true' => ['true', 'true'];
        yield 'zero' => ['0', '0'];
        yield 'json object' => ['{"a":1}', ['a' => 1]];
        yield 'plain text' => ['hello', 'hello'];
    }

    public function test_it_returns_a_short_message_when_the_target_is_unreachable_without_debug(): void
    {
        // Arrange

        config()->set('app.debug', false);

        $requestRelayActionMock = $this->mock(RequestRelayAction::class);

        // Anticipate

        $requestRelayActionMock
            ->shouldReceive('execute')
            ->andThrow(new ConnectionException('cURL error 7: Failed to connect'));

        // Act

        $response = $this->postJson(route('nimbus.api.relay'), [
            'method' => 'GET',
            'endpoint' => 'http://127.0.0.1:1/x',
        ]);

        // Assert

        $response->assertStatus(500);
        $response->assertExactJson(['message' => 'cURL error 7: Failed to connect']);
    }

    public function test_it_explains_a_timeout_against_the_same_host_and_port(): void
    {
        // Arrange

        $requestRelayActionMock = $this->mock(RequestRelayAction::class);

        // Anticipate

        $requestRelayActionMock
            ->shouldReceive('execute')
            ->andThrow(new ConnectionException('cURL error 28: Operation timed out'));

        // Act

        $response = $this->postJson(route('nimbus.api.relay'), [
            'method' => 'GET',
            'endpoint' => 'http://localhost/api/users',
        ]);

        // Assert

        $response->assertStatus(500);
        $this->assertStringContainsString('single threaded', $response->json('message'));
        $this->assertStringContainsString('http://localhost:81', $response->json('message'));
    }

    public function test_it_includes_the_trace_when_the_target_is_unreachable_while_debugging(): void
    {
        // Arrange

        config()->set('app.debug', true);

        $requestRelayActionMock = $this->mock(RequestRelayAction::class);

        // Anticipate

        $requestRelayActionMock
            ->shouldReceive('execute')
            ->andThrow(new ConnectionException('cURL error 7: Failed to connect'));

        // Act

        $response = $this->postJson(route('nimbus.api.relay'), [
            'method' => 'GET',
            'endpoint' => 'http://127.0.0.1:1/x',
        ]);

        // Assert

        $response->assertStatus(500);
        $response->assertJsonStructure(['message', 'exception', 'trace']);
    }

    public function test_it_forwards_uploaded_files_in_the_body(): void
    {
        // Arrange

        $relayResponseStub = new RelayedRequestResponseData(
            statusCode: 200,
            statusText: 'OK',
            body: new PrintableResponseBody(''),
            headers: [],
            durationMs: 1.5,
            timestamp: 1_700_000_000,
            cookies: [],
        );

        $requestRelayActionMock = $this->mock(RequestRelayAction::class);

        // Anticipate

        $requestRelayActionMock
            ->shouldReceive('execute')
            ->withAnyArgs()
            ->andReturn($relayResponseStub);

        // Act

        $response = $this->post(route('nimbus.api.relay'), [
            'method' => 'POST',
            'endpoint' => '/upload',
            'headers' => [['key' => 'Content-Type', 'value' => 'multipart/form-data']],
            'body' => [
                'title' => 'Holiday',
                'photos' => [
                    UploadedFile::fake()->createWithContent('first.txt', 'first'),
                    UploadedFile::fake()->createWithContent('second.txt', 'second'),
                ],
            ],
        ], ['Accept' => 'application/json']);

        // Assert

        $response->assertOk();

        $requestRelayActionMock
            ->shouldHaveReceived('execute')
            ->once()
            ->withArgs(function (RequestRelayData $data): bool {
                $this->assertSame('Holiday', $data->body['title']);
                $this->assertCount(2, $data->body['photos']);
                $this->assertContainsOnlyInstancesOf(UploadedFile::class, $data->body['photos']);
                $this->assertSame('first.txt', $data->body['photos'][0]->getClientOriginalName());

                return true;
            });
    }

    public function test_it_serializes_download_responses(): void
    {
        // Arrange

        $binary = "\xff\xfe\x00binary";

        $relayResponseStub = new RelayedRequestResponseData(
            statusCode: 200,
            statusText: 'OK',
            body: new PrintableResponseBody(''),
            headers: [],
            durationMs: 1.5,
            timestamp: 1_700_000_000,
            cookies: [],
            download: new DownloadResponseBody(
                filename: 'file.bin',
                mimeType: 'application/octet-stream',
                sizeInBytes: strlen($binary),
                content: base64_encode($binary),
            ),
        );

        $this->mock(RequestRelayAction::class)
            ->shouldReceive('execute')
            ->andReturn($relayResponseStub);

        // Act

        $response = $this->postJson(route('nimbus.api.relay'), [
            'method' => 'GET',
            'endpoint' => '/file',
        ]);

        // Assert

        $response->assertOk();

        $response->assertJsonPath('body', '');

        $response->assertJsonPath('download', [
            'filename' => 'file.bin',
            'mimeType' => 'application/octet-stream',
            'sizeInBytes' => strlen($binary),
            'content' => base64_encode($binary),
        ]);
    }

    public static function relayRequestProvider(): Generator
    {
        yield 'POST request without authorization' => [
            'payload' => [
                'method' => 'POST',
                'endpoint' => '/test-endpoint',
                'body' => ['test' => 'data'],
                'headers' => [
                    ['key' => 'Content-Type', 'value' => 'application/json'],
                ],
            ],
            'relayResponseStub' => new RelayedRequestResponseData(
                statusCode: 200,
                statusText: 'OK',
                body: new PrintableResponseBody('Hey!'),
                headers: [
                    'header1' => ['value1'],
                ],
                durationMs: fake('en')->randomFloat(),
                timestamp: fake('en')->dateTime()->getTimestamp(),
                cookies: [
                    new ResponseCookieValueObject(
                        key: 'cookie1',
                        rawValue: '::value::',
                        prefix: '::prefix::',
                    ),
                ],
            ),
        ];

        yield 'GET request with Bearer authorization' => [
            'payload' => [
                'method' => 'GET',
                'endpoint' => '/protected-endpoint',
                'authorization' => [
                    'type' => AuthorizationTypeEnum::Bearer->value,
                    'value' => 'test-token-123',
                ],
            ],
            'relayResponseStub' => new RelayedRequestResponseData(
                statusCode: 200,
                statusText: 'OK',
                body: new PrintableResponseBody('Authentoicated!'),
                headers: [
                    'header1' => ['value1'],
                ],
                durationMs: fake('en')->randomFloat(),
                timestamp: fake('en')->dateTime()->getTimestamp(),
                cookies: [
                    new ResponseCookieValueObject(
                        key: 'cookie1',
                        rawValue: '::value::',
                        prefix: '::prefix::',
                    ),
                ],
            ),
        ];

        yield 'GET request with invalid endpoint' => [
            'payload' => [
                'method' => 'GET',
                'endpoint' => '/non-existent-endpoint',
                'body' => ' ',
            ],
            'relayResponseStub' => new RelayedRequestResponseData(
                statusCode: 404,
                statusText: 'Not Found',
                body: new PrintableResponseBody('Not Found!'),
                headers: [
                    'header1' => ['value1'],
                ],
                durationMs: fake('en')->randomFloat(),
                timestamp: fake('en')->dateTime()->getTimestamp(),
                cookies: [],
            ),
        ];

        yield 'POST request with plain text body' => [
            'payload' => [
                'method' => 'POST',
                'endpoint' => '/test-endpoint',
                'body' => 'plain text content',
                'headers' => [
                    ['key' => 'Content-Type', 'value' => 'text/plain'],
                ],
            ],
            'relayResponseStub' => new RelayedRequestResponseData(
                statusCode: 200,
                statusText: 'OK',
                body: new PrintableResponseBody('Received plain text!'),
                headers: [
                    'header1' => ['value1'],
                ],
                durationMs: fake('en')->randomFloat(),
                timestamp: fake('en')->dateTime()->getTimestamp(),
                cookies: [],
            ),
        ];
    }

    #[DataProvider('validationFailureProvider')]
    public function test_it_validates_requests(array $payload, array $expectedErrors): void
    {
        // Act

        $response = $this->postJson(route('nimbus.api.relay'), $payload);

        // Assert

        $response->assertStatus(422);

        $response->assertJsonValidationErrors($expectedErrors);
    }

    public static function validationFailureProvider(): Generator
    {
        yield 'missing method' => [
            'payload' => [
                'endpoint' => '/test-endpoint',
            ],
            'expectedErrors' => ['method'],
        ];

        yield 'missing endpoint' => [
            'payload' => [
                'method' => 'GET',
            ],
            'expectedErrors' => ['endpoint'],
        ];

        yield 'current user authorization on a non current host environment' => [
            'payload' => [
                'method' => 'GET',
                'endpoint' => '/test-endpoint',
                'environment' => 'staging',
                'authorization' => ['type' => 'current-user'],
            ],
            'expectedErrors' => ['authorization.type'],
        ];

        yield 'impersonate authorization on a non current host environment' => [
            'payload' => [
                'method' => 'GET',
                'endpoint' => '/test-endpoint',
                'environment' => 'production',
                'authorization' => ['type' => 'impersonate', 'value' => '1'],
            ],
            'expectedErrors' => ['authorization.type'],
        ];

        yield 'method is an array' => [
            'payload' => [
                'method' => ['GET'],
                'endpoint' => '/test-endpoint',
            ],
            'expectedErrors' => ['method'],
        ];

        yield 'method is not a token' => [
            'payload' => [
                'method' => 'GET /admin',
                'endpoint' => '/test-endpoint',
            ],
            'expectedErrors' => ['method'],
        ];

        yield 'unsupported endpoint scheme' => [
            'payload' => [
                'method' => 'GET',
                'endpoint' => 'file:///etc/passwd',
            ],
            'expectedErrors' => ['endpoint'],
        ];

        yield 'invalid authorization type' => [
            'payload' => [
                'method' => 'GET',
                'endpoint' => '/test-endpoint',
                'authorization' => [
                    'type' => 'invalid-type',
                    'value' => 'test-value',
                ],
            ],
            'expectedErrors' => ['authorization.type'],
        ];
    }

    public function test_it_integrates(): void
    {
        // Arrange

        Route::get('/test-endpoint', fn () => response()->json(['message' => 'success']))
            ->name('test.endpoint');

        $payload = [
            'method' => 'GET',
            'endpoint' => '/test-endpoint',
            'body' => ['test' => 'data'],
        ];

        Http::fake([
            'test-endpoint?test=data' => Http::response('Hey!', 200),
        ]);

        // Act

        $response = $this->postJson(route('nimbus.api.relay'), $payload);

        // Assert

        $response->assertStatus(200);

        $response->assertJsonStructure([
            'statusCode',
            'statusText',
            'body',
            'headers',
            'cookies',
            'duration',
            'timestamp',
        ]);
    }

    public function test_it_rolls_back_database_changes_when_transaction_mode_is_enabled(): void
    {
        // Arrange

        // Create a test table for this test
        app('db')->statement('CREATE TABLE IF NOT EXISTS test_users (id INTEGER PRIMARY KEY, name TEXT)');

        Route::post('/test-transaction-rollback', function () {
            app('db')->table('test_users')->insert(['name' => 'Test User']);

            return response()->json(['message' => 'Users Count: '.app('db')->table('test_users')->count()]);
        })->name('test-transaction-rollback');

        // Act

        $response = $this->postJson(
            route('test-transaction-rollback'),
            [],
            ['X-Nimbus-Transaction-Mode' => '1']
        );

        // Assert

        $response->assertStatus(200);

        $response->assertJson([
            'message' => 'Users Count: 1',
        ]);

        // Verify the database change was rolled back
        $userCount = app('db')->table('test_users')->count();

        $this->assertEquals(0, $userCount, 'Database changes should be rolled back when transaction mode is enabled');

        // Cleanup

        app('db')->statement('DROP TABLE IF EXISTS test_users');
    }

    public function test_it_accepts_bearer_authorization_on_a_other_environment(): void
    {
        // Arrange

        $this->mock(RequestRelayAction::class)
            ->shouldReceive('execute')
            ->withArgs(function (RequestRelayData $data): bool {
                $this->assertSame('staging', $data->environment);
                $this->assertFalse($data->isCurrentHost);

                return true;
            })
            ->andReturn(new RelayedRequestResponseData(
                statusCode: 200,
                statusText: 'OK',
                body: new PrintableResponseBody('Hey!'),
                headers: [],
                durationMs: 1.0,
                timestamp: 1_700_000_000,
                cookies: [],
            ));

        // Act

        $response = $this->postJson(route('nimbus.api.relay'), [
            'method' => 'GET',
            'endpoint' => 'https://staging.example.com/api/users',
            'environment' => 'staging',
            'authorization' => ['type' => 'bearer', 'value' => 'token'],
        ]);

        // Assert

        $response->assertOk();
    }
}
