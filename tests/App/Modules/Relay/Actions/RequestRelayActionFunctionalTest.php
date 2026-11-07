<?php

namespace Sunchayn\Nimbus\Tests\App\Modules\Relay\Actions;

use Carbon\CarbonImmutable;
use Illuminate\Cookie\CookieValuePrefix;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Http\Client\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use Mockery;
use Mockery\MockInterface;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\TestWith;
use Ramsey\Uuid\Uuid;
use Sunchayn\Nimbus\Modules\Relay\Actions\RequestRelayAction;
use Sunchayn\Nimbus\Modules\Relay\DataTransferObjects\RelayedRequestResponseData;
use Sunchayn\Nimbus\Modules\Relay\DataTransferObjects\RequestRelayData;
use Sunchayn\Nimbus\Modules\Relay\Responses\DumpAndDieResponse;
use Sunchayn\Nimbus\Modules\Relay\Services\Authorization\DataTransferObjects\AuthorizationCredentials;
use Sunchayn\Nimbus\Modules\Relay\Services\Authorization\Enums\AuthorizationTypeEnum;
use Sunchayn\Nimbus\Modules\Relay\Services\Authorization\Handlers\AuthorizationHandler;
use Sunchayn\Nimbus\Modules\Relay\Services\Authorization\Handlers\AuthorizationHandlerFactory;
use Sunchayn\Nimbus\Modules\Relay\Services\Authorization\Handlers\RevokesAfterResponse;
use Sunchayn\Nimbus\Modules\Relay\Services\VarDumpParser\DataTransferObjects\ParseResultDto;
use Sunchayn\Nimbus\Modules\Relay\Services\VarDumpParser\VarDumpParser;
use Sunchayn\Nimbus\Modules\Relay\ValueObjects\ResponseCookieValueObject;
use Sunchayn\Nimbus\Modules\Relay\ValueObjects\SentRequest;
use Sunchayn\Nimbus\Tests\TestCase;
use Symfony\Component\HttpFoundation\ParameterBag;

#[CoversClass(RequestRelayAction::class)]
#[CoversClass(RequestRelayData::class)]
#[CoversClass(RelayedRequestResponseData::class)]
#[CoversClass(DumpAndDieResponse::class)]
class RequestRelayActionFunctionalTest extends TestCase
{
    private const ENDPOINT = 'https://localhost/api/test-endpoint';

    #[TestWith([200, 'OK'])]
    #[TestWith([404, 'Not Found'])]
    #[TestWith([301, 'Moved Permanently'])]
    #[TestWith([500, 'Internal Server Error'])]
    #[TestWith([419, 'Page Expired'])]
    public function test_it_relays_requests(
        int $stubStatusCode,
        string $expectedStatusText,
    ): void {
        // Arrange

        $this->app['session']->start();

        $requestData = new RequestRelayData(
            method: Arr::random([
                'POST',
                'GET',
                'PUT',
            ]),
            endpoint: self::ENDPOINT,
            authorization: $authorizationCredentials = $this->getRandomAuthorizationCredentials(),
            headers: [
                'Content-Type' => fake()->mimeType(),
                'X-Custom-Header' => $customHeaderValue = uniqid(),
            ],
            body: ['test' => 'data'],
            cookies: new ParameterBag,
        );

        CarbonImmutable::setTestNow(CarbonImmutable::now());

        $stubBody = ['message' => 'success', 'data' => ['test' => 'data']];

        $encryptedCookieOriginalValue = 'abc123xyzEncrypted';
        $encryptedCookieValue = $this->getEncryptedCookieValue('sessionIdEncrypted', $encryptedCookieOriginalValue);

        $stubHeaders = [
            'Set-Cookie' => [
                'sessionId=abc123xyz; Path=/; HttpOnly; Secure; SameSite=Strict; Expires=Mon, 30 Sep 2025 23:59:59 GMT',
                "sessionIdEncrypted={$encryptedCookieValue}; Path=/; HttpOnly; Secure; SameSite=Strict; Expires=Mon, 30 Sep 2025 23:59:59 GMT",
            ],
        ];

        // Anticipate

        Http::fake(function (Request $request) use ($stubStatusCode, $stubBody, $stubHeaders) {
            if ($request->url() !== self::ENDPOINT) {
                return null;
            }

            return Http::response(
                body: $stubBody + ['requestHeaders' => $request->headers()],
                status: $stubStatusCode,
                headers: $stubHeaders,
            );
        });

        $dummyAuthorizationHandler = $this->mock(
            AuthorizationHandler::class,
            fn (MockInterface $mock) => $mock->shouldReceive('authorize')
                ->with(Mockery::type(PendingRequest::class))
                ->andReturnArg(index: 0),
        );

        $this->mock(
            AuthorizationHandlerFactory::class,
            fn (MockInterface $mock) => $mock
                ->shouldReceive('create')
                ->with($authorizationCredentials, true)
                ->andReturn($dummyAuthorizationHandler),
        );

        $requestRelayAction = resolve(RequestRelayAction::class);

        // Act

        $response = $requestRelayAction->execute($requestData);

        // Assert

        $this->assertEquals($stubStatusCode, $response->statusCode);

        $this->assertEquals($expectedStatusText, $response->statusText);

        $this->assertEquals(
            $stubBody,
            Arr::except(
                $response->body->body,
                'requestHeaders',
            ),
        );

        $this->assertEquals(
            $customHeaderValue,
            $response->body->body['requestHeaders']['X-Custom-Header'][0] ?? -1,
        );

        $this->assertEquals(
            csrf_token(),
            $response->body->body['requestHeaders']['X-CSRF-TOKEN'][0] ?? null,
        );

        $this->assertEquals(
            CarbonImmutable::now()->timestamp,
            $response->timestamp,
        );

        $this->assertRelayResponseCookies(
            expectedCookies: [
                [
                    'name' => 'sessionId',
                    'raw' => 'abc123xyz',
                    'decrypted' => null,
                ],
                [
                    'name' => 'sessionIdEncrypted',
                    'raw' => $encryptedCookieValue,
                    'decrypted' => $encryptedCookieOriginalValue,
                ],
            ],
            actualCookies: $response->cookies,
        );

        $this->assertEquals(
            [
                'Content-Type' => ['application/json'],
                ...$stubHeaders,
            ],
            $response->headers,
        );

        $this->assertEqualsWithDelta(
            5,
            $response->durationMs,
            delta: 50, // <- Sweet spot for fluctuation.
        );
    }

    #[TestWith(['get'])]
    #[TestWith(['head'])]
    public function test_it_merges_body_into_query_parameters_for_get_and_head_requests(string $method): void
    {
        // Arrange

        $bodyData = ['filter' => 'active', 'sort' => 'desc'];

        $queryParameters = ['page' => '1', 'limit' => '10'];

        $requestData = new RequestRelayData(
            method: $method,
            endpoint: self::ENDPOINT,
            authorization: AuthorizationCredentials::none(),
            headers: ['X-Custom-Header' => 'test'],
            body: $bodyData,
            cookies: new ParameterBag,
            queryParameters: $queryParameters,
        );

        // Anticipate

        Http::fake(function (Request $request) use ($bodyData, $queryParameters) {
            // Assert that body data is merged into query parameters
            $expectedQueryParams = array_merge($queryParameters, $bodyData);

            foreach ($expectedQueryParams as $key => $value) {
                if (! str_contains($request->url(), "{$key}={$value}")) {
                    return Http::response(['error' => 'Missing query parameter'], 400);
                }
            }

            // Assert that the request body is empty
            if (! empty($request->body())) {
                return Http::response(['error' => 'Body should be empty'], 400);
            }

            return Http::response([
                'success' => true,
            ]);
        });

        $this->mockAuthorizationHandler();

        $requestRelayAction = resolve(RequestRelayAction::class);

        // Act

        $response = $requestRelayAction->execute($requestData);

        // Assert

        $this->assertEquals(200, $response->statusCode);

        $this->assertEquals(
            [
                'success' => true,
            ],
            $response->body->body,
        );
    }

    #[TestWith(['get'])]
    #[TestWith(['head'])]
    public function test_it_does_not_merge_string_body_into_query_parameters_for_get_and_head_requests(string $method): void
    {
        // Arrange

        $bodyData = 'plain text content';

        $queryParameters = ['page' => '1'];

        $requestData = new RequestRelayData(
            method: $method,
            endpoint: self::ENDPOINT,
            authorization: AuthorizationCredentials::none(),
            headers: [],
            body: $bodyData,
            cookies: new ParameterBag,
            queryParameters: $queryParameters,
        );

        // Anticipate

        Http::fake(function (Request $request) use ($queryParameters) {
            // Assert that the request URL contains the original query parameters
            foreach ($queryParameters as $key => $value) {
                if (! str_contains($request->url(), "{$key}={$value}")) {
                    return Http::response(['error' => 'Missing query parameter'], 400);
                }
            }

            // Assert that the request URL does NOT contain the string body
            if (str_contains($request->url(), 'plain+text+content') || str_contains($request->url(), 'plain%20text%20content')) {
                return Http::response(['error' => 'Body should not be in query parameters'], 400);
            }

            return Http::response([
                'success' => true,
            ]);
        });

        $this->mockAuthorizationHandler();

        $requestRelayAction = resolve(RequestRelayAction::class);

        // Act

        $response = $requestRelayAction->execute($requestData);

        // Assert

        $this->assertEquals(200, $response->statusCode);
    }

    public function test_it_sends_json_body_by_default(): void
    {
        // Arrange

        $bodyData = ['user' => 'john', 'action' => 'login'];

        $requestData = new RequestRelayData(
            method: 'post',
            endpoint: self::ENDPOINT,
            authorization: AuthorizationCredentials::none(),
            headers: [], // <- Content-Type is missing => Json by default.
            body: $bodyData,
            cookies: new ParameterBag,
        );

        // Anticipate

        Http::fake(function (Request $request) use ($bodyData) {
            $requestBody = json_decode($request->body(), true);

            return Http::response([
                'receivedBody' => $requestBody,
                'bodyMatches' => $requestBody === $bodyData,
            ], 200);
        });

        $this->mockAuthorizationHandler();

        $requestRelayAction = resolve(RequestRelayAction::class);

        // Act

        $response = $requestRelayAction->execute($requestData);

        // Assert

        $this->assertTrue($response->body->body['bodyMatches'], 'POST body should be sent as JSON');

        $this->assertEquals($bodyData, $response->body->body['receivedBody']);
    }

    public function test_it_sends_a_single_content_type_header_with_a_json_body(): void
    {
        // Arrange

        $requestData = new RequestRelayData(
            method: 'post',
            endpoint: self::ENDPOINT,
            authorization: AuthorizationCredentials::none(),
            headers: ['content-type' => 'application/vnd.api+json'],
            body: ['user' => 'john'],
            cookies: new ParameterBag,
        );

        // Anticipate

        Http::fake();

        $this->mockAuthorizationHandler();

        $requestRelayAction = resolve(RequestRelayAction::class);

        // Act

        $requestRelayAction->execute($requestData);

        // Assert

        Http::assertSent(function (Request $request): bool {
            $this->assertSame(['application/vnd.api+json'], $request->header('Content-Type'));

            return true;
        });
    }

    public function test_it_sends_files_as_multipart_parts(): void
    {
        // Arrange

        $requestData = new RequestRelayData(
            method: 'post',
            endpoint: self::ENDPOINT,
            authorization: AuthorizationCredentials::none(),
            headers: ['content-type' => 'multipart/form-data'],
            body: [
                'title' => 'Holiday',
                'photos' => [
                    UploadedFile::fake()->createWithContent('first.txt', 'first contents'),
                    UploadedFile::fake()->createWithContent('second.txt', 'second contents'),
                ],
                'user' => ['avatar' => UploadedFile::fake()->createWithContent('me.txt', 'avatar contents')],
            ],
            cookies: new ParameterBag,
        );

        // Anticipate

        Http::fake();

        $this->mockAuthorizationHandler();

        $requestRelayAction = resolve(RequestRelayAction::class);

        // Act

        $requestRelayAction->execute($requestData);

        // Assert

        Http::assertSent(function (Request $request): bool {
            $this->assertStringStartsWith('multipart/form-data; boundary=', $request->header('Content-Type')[0]);

            $body = $request->body();

            $this->assertMultipartPart($body, 'name="title"', 'Holiday');
            $this->assertMultipartPart($body, 'name="photos[]"; filename="first.txt"', 'first contents');
            $this->assertMultipartPart($body, 'name="photos[]"; filename="second.txt"', 'second contents');
            $this->assertMultipartPart($body, 'name="user[avatar]"; filename="me.txt"', 'avatar contents');

            return true;
        });
    }

    public function test_it_sends_text_only_form_data_as_multipart(): void
    {
        // Arrange

        $requestData = new RequestRelayData(
            method: 'post',
            endpoint: self::ENDPOINT,
            authorization: AuthorizationCredentials::none(),
            headers: ['content-type' => 'multipart/form-data'],
            body: ['name' => 'john', 'tags' => ['a', 'b']],
            cookies: new ParameterBag,
        );

        // Anticipate

        Http::fake();

        $this->mockAuthorizationHandler();

        $requestRelayAction = resolve(RequestRelayAction::class);

        // Act

        $requestRelayAction->execute($requestData);

        // Assert

        Http::assertSent(function (Request $request): bool {
            $this->assertStringStartsWith('multipart/form-data; boundary=', $request->header('Content-Type')[0]);

            $body = $request->body();

            $this->assertMultipartPart($body, 'name="name"', 'john');
            $this->assertMultipartPart($body, 'name="tags[]"', 'a');
            $this->assertMultipartPart($body, 'name="tags[]"', 'b');

            return true;
        });
    }

    public function test_it_relays_plain_text_body(): void
    {
        // Arrange

        $bodyData = 'plain text content';

        $requestData = new RequestRelayData(
            method: 'post',
            endpoint: self::ENDPOINT,
            authorization: AuthorizationCredentials::none(),
            headers: ['Content-Type' => 'text/plain'],
            body: $bodyData,
            cookies: new ParameterBag,
        );

        // Anticipate

        Http::fake(function (Request $request) use ($bodyData) {
            return Http::response([
                'receivedBody' => $request->body(),
                'bodyMatches' => $request->body() === $bodyData,
            ], 200);
        });

        $this->mockAuthorizationHandler();

        $requestRelayAction = resolve(RequestRelayAction::class);

        // Act

        $response = $requestRelayAction->execute($requestData);

        // Assert

        $this->assertTrue($response->body->body['bodyMatches'], 'POST body should be sent as plain text');

        $this->assertEquals($bodyData, $response->body->body['receivedBody']);
    }

    public function test_it_url_decodes_cookie_values(): void
    {
        // Arrange

        $requestData = new RequestRelayData(
            method: 'get',
            endpoint: self::ENDPOINT,
            authorization: AuthorizationCredentials::none(),
            headers: [],
            body: [],
            cookies: new ParameterBag,
        );

        $encodedValue = urlencode('test value with spaces');

        $stubHeaders = [
            'Set-Cookie' => [
                "testCookie={$encodedValue}; Path=/; HttpOnly",
            ],
        ];

        // Anticipate

        Http::fake(fn () => Http::response(['success' => true], 200, $stubHeaders));

        $this->mockAuthorizationHandler();

        $requestRelayAction = resolve(RequestRelayAction::class);

        // Act

        $response = $requestRelayAction->execute($requestData);

        // Assert

        $this->assertCount(1, $response->cookies);

        $this->assertEquals('test value with spaces', $response->cookies[0]->toArray()['value']['raw']);
    }

    public function test_it_parses_dump_and_die_responses(): void
    {
        // Arrange

        $this->freezeTime();

        $requestData = new RequestRelayData(
            method: 'GET',
            endpoint: self::ENDPOINT,
            authorization: AuthorizationCredentials::none(),
            headers: [
                'Content-Type' => 'application/json',
                'X-Custom-Header' => $customHeaderValue = uniqid(),
            ],
            body: ['test' => 'data'],
            cookies: new ParameterBag,
        );

        $uuid = fake()->uuid;
        $stubSource = fake()->filePath();
        $stubDumps = [
            'type' => fake()->word(),
            'value' => fake()->word(),
        ];

        $parseResultDtoMock = Mockery::mock(ParseResultDto::class);
        $varDumpParserMock = $this->mock(VarDumpParser::class);

        $dumpHtml = '<script> Sfdump = window.Sfdump;</script><span>Hello World!</span>';

        // Anticipate

        Str::createUuidsUsing(fn () => Uuid::fromString($uuid));

        Http::fake(fn (Request $request) => Http::response(
            body: $dumpHtml,
            status: 500,
            headers: [],
        ));

        $parseResultDtoMock
            ->shouldReceive('toArray')
            ->andReturn([
                'source' => $stubSource,
                'dumps' => $stubDumps,
            ])
            ->once();

        $varDumpParserMock->shouldReceive('parse')->with($dumpHtml)->andReturn($parseResultDtoMock)->once();

        // Act

        $response = resolve(RequestRelayAction::class)->execute($requestData);

        // Assert

        $this->assertEquals(DumpAndDieResponse::DUMP_AND_DIE_STATUS_CODE, $response->statusCode);

        $this->assertEquals(
            [
                'id' => $uuid,
                'timestamp' => now()->format('Y-m-d H:i:s'),
                'source' => $stubSource,
                'dumps' => $stubDumps,
            ],
            $response->body->body,
        );
    }

    public function test_it_relays_transaction_mode_header(): void
    {
        // Arrange

        $requestData = new RequestRelayData(
            method: 'POST',
            endpoint: self::ENDPOINT,
            authorization: AuthorizationCredentials::none(),
            headers: [
                'Content-Type' => 'application/json',
            ],
            body: ['test' => 'data'],
            cookies: new ParameterBag,
            transactionMode: true,
        );

        // Anticipate

        Http::fake(function (Request $request) {
            return Http::response([
                'receivedHeaders' => $request->headers(),
            ], 200);
        });

        $this->mockAuthorizationHandler();

        $requestRelayAction = resolve(RequestRelayAction::class);

        // Act

        $response = $requestRelayAction->execute($requestData);

        // Assert

        $this->assertEquals(200, $response->statusCode);

        $this->assertArrayHasKey(
            'x-nimbus-transaction-mode',
            $response->body->body['receivedHeaders'],
            'The transaction mode header should be present in the relayed request.',
        );

        $this->assertEquals(
            '1',
            $response->body->body['receivedHeaders']['x-nimbus-transaction-mode'][0],
            'The transaction mode header should have the value "1".',
        );
    }

    public function test_it_relays_time_travel_header(): void
    {
        // Arrange

        $requestData = new RequestRelayData(
            method: 'POST',
            endpoint: self::ENDPOINT,
            authorization: AuthorizationCredentials::none(),
            headers: [
                'Content-Type' => 'application/json',
            ],
            body: ['test' => 'data'],
            cookies: new ParameterBag,
            timeTravel: '+31 days',
        );

        // Anticipate

        Http::fake(function (Request $request) {
            return Http::response([
                'receivedHeaders' => $request->headers(),
            ], 200);
        });

        $this->mockAuthorizationHandler();

        $requestRelayAction = resolve(RequestRelayAction::class);

        // Act

        $response = $requestRelayAction->execute($requestData);

        // Assert

        $this->assertEquals(200, $response->statusCode);

        $this->assertArrayHasKey(
            'x-nimbus-time-travel',
            $response->body->body['receivedHeaders'],
            'The time travel header should be present in the relayed request.',
        );

        $this->assertEquals(
            '+31 days',
            $response->body->body['receivedHeaders']['x-nimbus-time-travel'][0],
            'The time travel header should keep its value.',
        );
    }

    public function test_it_revokes_what_the_handler_created_even_when_sending_fails(): void
    {
        // Arrange

        $handler = Mockery::mock(AuthorizationHandler::class, RevokesAfterResponse::class);
        $handler->shouldReceive('authorize')->andReturnArg(0);
        $handler->shouldReceive('revoke')->once();

        $this->mock(
            AuthorizationHandlerFactory::class,
            fn (MockInterface $mock) => $mock->shouldReceive('create')->andReturn($handler),
        );

        Http::fake(fn () => throw new ConnectionException('Connection refused.'));

        $requestData = new RequestRelayData(
            method: 'GET',
            endpoint: self::ENDPOINT,
            authorization: AuthorizationCredentials::none(),
            headers: [],
            body: [],
            cookies: new ParameterBag,
        );

        // Anticipate

        $this->expectException(ConnectionException::class);

        // Act

        resolve(RequestRelayAction::class)->execute($requestData);
    }

    public function test_it_auto_injects_csrf_token_header_when_missing(): void
    {
        // Arrange

        $this->app['session']->start();

        $requestData = new RequestRelayData(
            method: 'POST',
            endpoint: self::ENDPOINT,
            authorization: AuthorizationCredentials::none(),
            headers: [],
            body: [],
            cookies: new ParameterBag,
        );

        Http::fake(function (Request $request) {
            return Http::response([
                'receivedHeaders' => $request->headers(),
            ], 200);
        });

        $this->mockAuthorizationHandler();

        $requestRelayAction = resolve(RequestRelayAction::class);

        // Act

        $response = $requestRelayAction->execute($requestData);

        // Assert

        $this->assertEquals(200, $response->statusCode);

        $this->assertArrayHasKey(
            'X-CSRF-TOKEN',
            $response->body->body['receivedHeaders'],
        );

        $this->assertEquals(
            csrf_token(),
            $response->body->body['receivedHeaders']['X-CSRF-TOKEN'][0],
        );
    }

    public function test_it_preserves_explicit_csrf_token_header(): void
    {
        // Arrange

        $customCsrfToken = 'custom-csrf-token-12345';

        $requestData = new RequestRelayData(
            method: 'POST',
            endpoint: self::ENDPOINT,
            authorization: AuthorizationCredentials::none(),
            headers: ['X-CSRF-TOKEN' => $customCsrfToken],
            body: [],
            cookies: new ParameterBag,
        );

        Http::fake(function (Request $request) {
            return Http::response([
                'receivedHeaders' => $request->headers(),
            ], 200);
        });

        $this->mockAuthorizationHandler();

        $requestRelayAction = resolve(RequestRelayAction::class);

        // Act

        $response = $requestRelayAction->execute($requestData);

        // Assert

        $this->assertEquals(200, $response->statusCode);

        $this->assertEquals(
            $customCsrfToken,
            $response->body->body['receivedHeaders']['X-CSRF-TOKEN'][0],
        );
    }

    public function test_it_doesnt_crash_when_session_is_not_bound(): void
    {
        // Arrange

        $this->app->singleton('session', null);

        $requestData = new RequestRelayData(
            method: 'POST',
            endpoint: self::ENDPOINT,
            authorization: AuthorizationCredentials::none(),
            headers: [],
            body: [],
            cookies: new ParameterBag,
        );

        Http::fake(function (Request $request) {
            return Http::response([
                'receivedHeaders' => $request->headers(),
            ], 200);
        });

        $this->mockAuthorizationHandler();

        $requestRelayAction = resolve(RequestRelayAction::class);

        // Act

        $response = $requestRelayAction->execute($requestData);

        // Assert

        $this->assertEquals(200, $response->statusCode);
    }

    public function test_it_forwards_download_responses(): void
    {
        // Arrange

        $binary = "PK\x03\x04\xff\xfe\x00binary";

        $requestData = new RequestRelayData(
            method: 'GET',
            endpoint: self::ENDPOINT,
            authorization: AuthorizationCredentials::none(),
            headers: [],
            body: [],
            cookies: new ParameterBag,
        );

        Http::fake(fn () => Http::response($binary, 200, [
            'Content-Type' => 'application/zip',
            'Content-Disposition' => 'attachment; filename="archive.zip"',
        ]));

        $this->mockAuthorizationHandler();

        $requestRelayAction = resolve(RequestRelayAction::class);

        // Act

        $response = $requestRelayAction->execute($requestData);

        // Assert

        $this->assertSame(200, $response->statusCode);
        $this->assertSame('', $response->body->body);
        $this->assertSame('archive.zip', $response->download?->filename);
        $this->assertSame('application/zip', $response->download?->mimeType);
        $this->assertSame(strlen($binary), $response->download?->sizeInBytes);
        $this->assertSame(base64_encode($binary), $response->download?->content);
    }

    /*
     * Helpers.
     */

    private function assertMultipartPart(string $body, string $disposition, string $contents): void
    {
        $this->assertMatchesRegularExpression(
            '/'.preg_quote($disposition, '/').'\r\n(?:[A-Za-z-]+: [^\r]*\r\n)*\r\n'.preg_quote($contents, '/').'\r\n/',
            $body,
        );
    }

    private function getRandomAuthorizationCredentials(): AuthorizationCredentials
    {
        return Arr::random([
            AuthorizationCredentials::none(),
            new AuthorizationCredentials(AuthorizationTypeEnum::Basic, ['username' => 'user', 'password' => 'pass']),
            new AuthorizationCredentials(AuthorizationTypeEnum::Bearer, 'foobar'),
            new AuthorizationCredentials(AuthorizationTypeEnum::Impersonate, '14'),
            new AuthorizationCredentials(AuthorizationTypeEnum::CurrentUser, value: null),
        ]);
    }

    private function getEncryptedCookieValue(string $cookieName, string $rawValue): string
    {
        return app('encrypter')
            ->encrypt(
                value: CookieValuePrefix::create($cookieName, app('encrypter')->getKey()).$rawValue,
                serialize: false,
            );
    }

    private function mockAuthorizationHandler(): void
    {
        $dummyAuthorizationHandler = $this->mock(
            AuthorizationHandler::class,
            fn (MockInterface $mock) => $mock->shouldReceive('authorize')
                ->andReturnArg(0),
        );

        $this->mock(
            AuthorizationHandlerFactory::class,
            fn (MockInterface $mock) => $mock
                ->shouldReceive('create')
                ->andReturn($dummyAuthorizationHandler),
        );
    }

    /*
     * Asserts.
     */

    private function assertRelayResponseCookies(array $expectedCookies, array $actualCookies): void
    {
        $this->assertCount(
            count($expectedCookies),
            $actualCookies,
        );

        $this->assertContainsOnlyInstancesOf(
            ResponseCookieValueObject::class,
            $actualCookies,
        );

        foreach ($expectedCookies as $index => $expectedCookie) {
            $this->assertEquals(
                [
                    'key' => $expectedCookie['name'],
                    'value' => [
                        'raw' => $expectedCookie['raw'],
                        'decrypted' => $expectedCookie['decrypted'],
                    ],
                ],
                $actualCookies[$index]->toArray(),
            );
        }
    }

    public function test_it_skips_host_credentials_and_cookie_decryption_for_other_environments(): void
    {
        // Arrange

        $this->app['session']->start();

        $requestData = new RequestRelayData(
            method: 'get',
            endpoint: self::ENDPOINT,
            authorization: AuthorizationCredentials::none(),
            headers: [],
            body: [],
            cookies: new ParameterBag,
            environment: 'staging',
            isCurrentHost: false,
        );

        $encryptedCookieValue = $this->getEncryptedCookieValue('sessionIdEncrypted', 'secret');

        // Anticipate

        Http::fake(fn (Request $request) => Http::response(
            body: ['requestHeaders' => $request->headers()],
            headers: ['Set-Cookie' => ["sessionIdEncrypted={$encryptedCookieValue}; Path=/"]],
        ));

        $requestRelayAction = resolve(RequestRelayAction::class);

        // Act

        $response = $requestRelayAction->execute($requestData);

        // Assert

        $this->assertArrayNotHasKey('X-CSRF-TOKEN', $response->body->body['requestHeaders']);

        $this->assertRelayResponseCookies(
            expectedCookies: [
                ['name' => 'sessionIdEncrypted', 'raw' => $encryptedCookieValue, 'decrypted' => null],
            ],
            actualCookies: $response->cookies,
        );
    }

    #[TestWith(['production', true])]
    #[TestWith(['staging', false])]
    #[TestWith(['current', false])]
    public function test_it_verifies_ssl_only_for_production(string $environment, bool $expectedVerify): void
    {
        // Arrange

        $requestData = new RequestRelayData(
            method: 'get',
            endpoint: self::ENDPOINT,
            authorization: AuthorizationCredentials::none(),
            headers: [],
            body: [],
            cookies: new ParameterBag,
            environment: $environment,
            isCurrentHost: $environment === 'current',
        );

        $capturedVerify = null;

        // Anticipate

        Http::fake(function (Request $request, array $options) use (&$capturedVerify) {
            $capturedVerify = $options['verify'] ?? null;

            return Http::response([]);
        });

        $requestRelayAction = resolve(RequestRelayAction::class);

        // Act

        $requestRelayAction->execute($requestData);

        // Assert

        $this->assertSame($expectedVerify, $capturedVerify);
    }

    public function test_it_verifies_ssl_for_current_host_detected_as_production(): void
    {
        // Arrange

        config()->set('nimbus.environments', [
            'production' => ['name' => 'Production', 'base_url' => 'https://prod.test', 'enabled' => true],
        ]);
        config()->set('app.url', 'https://prod.test');

        $requestData = new RequestRelayData(
            method: 'get',
            endpoint: self::ENDPOINT,
            authorization: AuthorizationCredentials::none(),
            headers: [],
            body: [],
            cookies: new ParameterBag,
            environment: 'current',
            isCurrentHost: true,
        );

        $capturedVerify = null;

        // Anticipate

        Http::fake(function (Request $request, array $options) use (&$capturedVerify) {
            $capturedVerify = $options['verify'] ?? null;

            return Http::response([]);
        });

        // Act

        resolve(RequestRelayAction::class)->execute($requestData);

        // Assert

        $this->assertTrue($capturedVerify);
    }

    public function test_it_applies_request_options(): void
    {
        // Arrange

        $requestData = new RequestRelayData(
            method: 'get',
            endpoint: self::ENDPOINT,
            authorization: AuthorizationCredentials::none(),
            headers: [],
            body: [],
            cookies: new ParameterBag,
            followRedirects: false,
            timeout: 7,
            verifySsl: true,
        );

        $capturedOptions = [];

        // Anticipate

        Http::fake(function (Request $request, array $options) use (&$capturedOptions) {
            $capturedOptions = $options;

            return Http::response([]);
        });

        // Act

        resolve(RequestRelayAction::class)->execute($requestData);

        // Assert

        $this->assertFalse($capturedOptions['allow_redirects']);
        $this->assertSame(7, $capturedOptions['timeout']);
        $this->assertTrue($capturedOptions['verify']);
    }

    public function test_it_recognizes_multipart_content_type_with_parameters(): void
    {
        // Arrange

        $requestData = new RequestRelayData(
            method: 'post',
            endpoint: self::ENDPOINT,
            authorization: AuthorizationCredentials::none(),
            headers: ['content-type' => 'Multipart/Form-Data; charset=utf-8'],
            body: ['title' => 'Holiday'],
            cookies: new ParameterBag,
        );

        // Anticipate

        Http::fake();

        $this->mockAuthorizationHandler();

        // Act

        resolve(RequestRelayAction::class)->execute($requestData);

        // Assert

        Http::assertSent(function (Request $request): bool {
            $this->assertStringStartsWith('multipart/form-data; boundary=', $request->header('Content-Type')[0]);

            return true;
        });
    }

    public function test_it_returns_the_request_as_sent(): void
    {
        // Arrange

        $requestData = new RequestRelayData(
            method: 'post',
            endpoint: self::ENDPOINT,
            authorization: AuthorizationCredentials::none(),
            headers: ['x-custom' => 'yes'],
            body: ['name' => 'Ada'],
            cookies: new ParameterBag,
            queryParameters: ['page' => '2'],
        );

        // Anticipate

        Http::fake();

        $this->mockAuthorizationHandler();

        // Act

        $result = resolve(RequestRelayAction::class)->execute($requestData);

        // Assert

        $sent = $result->sent;

        $this->assertSame('POST', $sent->method);
        $this->assertSame(self::ENDPOINT.'?page=2', $sent->url);
        $this->assertSame('{"name":"Ada"}', $sent->body);
        $this->assertFalse($sent->bodyTruncated);
        $this->assertContains(['key' => 'x-custom', 'value' => 'yes'], $sent->headers);
    }

    public function test_it_truncates_a_large_sent_body(): void
    {
        // Arrange

        $requestData = new RequestRelayData(
            method: 'post',
            endpoint: self::ENDPOINT,
            authorization: AuthorizationCredentials::none(),
            headers: [],
            body: str_repeat('a', SentRequest::MAX_BODY_BYTES + 10),
            cookies: new ParameterBag,
        );

        // Anticipate

        Http::fake();

        $this->mockAuthorizationHandler();

        // Act

        $sent = resolve(RequestRelayAction::class)->execute($requestData)->sent;

        // Assert

        $this->assertSame(SentRequest::MAX_BODY_BYTES, strlen($sent->body));
        $this->assertTrue($sent->bodyTruncated);
    }

    public function test_it_builds_urlencoded_bodies_with_http_build_query(): void
    {
        // Arrange

        $requestData = new RequestRelayData(
            method: 'post',
            endpoint: self::ENDPOINT,
            authorization: AuthorizationCredentials::none(),
            headers: ['content-type' => 'application/x-www-form-urlencoded'],
            body: ['name' => 'Ada Lovelace', 'tags' => ['a', 'b']],
            cookies: new ParameterBag,
        );

        // Anticipate

        Http::fake();

        $this->mockAuthorizationHandler();

        // Act

        resolve(RequestRelayAction::class)->execute($requestData);

        // Assert

        Http::assertSent(function (Request $request): bool {
            $this->assertSame('application/x-www-form-urlencoded', $request->header('Content-Type')[0]);
            $this->assertSame(http_build_query(['name' => 'Ada Lovelace', 'tags' => ['a', 'b']]), $request->body());

            return true;
        });
    }
}
