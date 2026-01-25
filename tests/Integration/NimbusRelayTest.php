<?php

namespace Sunchayn\Nimbus\Tests\Integration;

use Generator;
use Illuminate\Foundation\Http\Middleware\ConvertEmptyStringsToNull;
use Illuminate\Foundation\Http\Middleware\TrimStrings;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Route;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use Sunchayn\Nimbus\Http\Api\Relay\NimbusRelayController;
use Sunchayn\Nimbus\Http\Api\Relay\NimbusRelayRequest;
use Sunchayn\Nimbus\Http\Api\Relay\RelayResponseResource;
use Sunchayn\Nimbus\Modules\Relay\Actions\RequestRelayAction;
use Sunchayn\Nimbus\Modules\Relay\Authorization\AuthorizationTypeEnum;
use Sunchayn\Nimbus\Modules\Relay\DataTransferObjects\RelayedRequestResponseData;
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
}
