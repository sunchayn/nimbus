<?php

namespace Sunchayn\Nimbus\Tests\App\Modules\Relay\Actions;

use Carbon\CarbonImmutable;
use Illuminate\Cookie\CookieValuePrefix;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Http;
use Mockery;
use Mockery\MockInterface;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\TestWith;
use Sunchayn\Nimbus\Modules\Relay\Actions\RequestRelayAction;
use Sunchayn\Nimbus\Modules\Relay\Authorization\AuthorizationCredentials;
use Sunchayn\Nimbus\Modules\Relay\Authorization\AuthorizationTypeEnum;
use Sunchayn\Nimbus\Modules\Relay\Authorization\Handlers\AuthorizationHandler;
use Sunchayn\Nimbus\Modules\Relay\Authorization\Handlers\AuthorizationHandlerFactory;
use Sunchayn\Nimbus\Modules\Relay\DataTransferObjects\RelayedRequestResponseData;
use Sunchayn\Nimbus\Modules\Relay\DataTransferObjects\RequestRelayData;
use Sunchayn\Nimbus\Modules\Relay\ValueObjects\ResponseCookieValueObject;
use Sunchayn\Nimbus\Tests\TestCase;
use Symfony\Component\HttpFoundation\ParameterBag;

#[CoversClass(RequestRelayAction::class)]
#[CoversClass(RequestRelayData::class)]
#[CoversClass(RelayedRequestResponseData::class)]
class RequestRelayActionFunctionalTest extends TestCase
{
    private const ENDPOINT = 'https://localhost/api/test-endpoint';

    #[TestWith([200, 'OK'])]
    #[TestWith([404, 'Not Found'])]
    #[TestWith([301, 'Moved Permanently'])]
    #[TestWith([500, 'Internal Server Error'])]
    #[TestWith([419, 'Method Not Allowed'])]
    public function test_it_relays_requests(
        int $stubStatusCode,
        string $expectedStatusText,
    ): void {
        // Arrange

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
                ->with($authorizationCredentials)
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

    /*
     * Helpers.
     */

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
}
