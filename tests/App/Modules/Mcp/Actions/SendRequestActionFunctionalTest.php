<?php

declare(strict_types=1);

namespace Sunchayn\Nimbus\Tests\App\Modules\Mcp\Actions;

use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Contracts\Auth\Guard;
use Illuminate\Contracts\Auth\UserProvider;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\RateLimiter;
use Mockery\MockInterface;
use PHPUnit\Framework\Attributes\CoversClass;
use Sunchayn\Nimbus\Modules\Config\ActiveApplicationResolver;
use Sunchayn\Nimbus\Modules\Magic\DataTransferObjects\InsightData;
use Sunchayn\Nimbus\Modules\Magic\Enums\NimbusHeaderEnum;
use Sunchayn\Nimbus\Modules\Magic\Services\Insights\Results\ExceptionResult;
use Sunchayn\Nimbus\Modules\Magic\Services\Insights\Results\GatesResult;
use Sunchayn\Nimbus\Modules\Magic\Services\Insights\Results\LogsResult;
use Sunchayn\Nimbus\Modules\Magic\Services\Insights\Results\QueriesResult;
use Sunchayn\Nimbus\Modules\Mcp\Actions\SendRequestAction;
use Sunchayn\Nimbus\Modules\Mcp\Contracts\RequestExecutor;
use Sunchayn\Nimbus\Modules\Mcp\DataTransferObjects\ExecutionRequestData;
use Sunchayn\Nimbus\Modules\Mcp\DataTransferObjects\ExecutionResultData;
use Sunchayn\Nimbus\Modules\Mcp\DataTransferObjects\SendRequestData;
use Sunchayn\Nimbus\Modules\Mcp\Exceptions\McpToolException;
use Sunchayn\Nimbus\Modules\Mcp\Services\Executors\HttpRequestExecutor;
use Sunchayn\Nimbus\Modules\Routes\Actions\ExtractRoutesAction;
use Sunchayn\Nimbus\Modules\Routes\Collections\ExtractedRoutesCollection;
use Sunchayn\Nimbus\Modules\Routes\DataTransferObjects\ExtractedRoute;
use Sunchayn\Nimbus\Modules\Routes\ValueObjects\Endpoint;
use Sunchayn\Nimbus\Modules\Schemas\ValueObjects\Schema;
use Sunchayn\Nimbus\Tests\App\Modules\Mcp\Concerns\FakesExtractedRoutes;
use Sunchayn\Nimbus\Tests\Concerns\UsesTemporaryBasePath;
use Sunchayn\Nimbus\Tests\TestCase;

#[CoversClass(SendRequestAction::class)]
class SendRequestActionFunctionalTest extends TestCase
{
    use FakesExtractedRoutes;
    use UsesTemporaryBasePath;

    private MockInterface $executorMock;

    protected function setUp(): void
    {
        parent::setUp();

        $this->useTemporaryBasePath();

        RateLimiter::clear(SendRequestAction::RATE_LIMIT_KEY);

        $this->app['config']->set('nimbus.mcp', [
            'rate_limit' => 60,
            'body_limit' => 20000,
        ]);

        $this->fakeExecutorResult(
            new ExecutionResultData(
                status: 200,
                headers: [
                    'date' => 'now',
                    'server' => 'x',
                    'content-type' => 'application/json',
                    'set-cookie' => 'a=b',
                ],
                body: '{"ok":true,"api_token":"abc"}',
                durationMs: 3.0,
            ),
        );
    }

    public function test_it_rolls_back_a_write_request(): void
    {
        // Arrange

        $this->fakeExtractedRoutes();

        $data = new SendRequestData(
            route: 'POST api/users',
            body: ['name' => 'Ada'],
        );

        $action = resolve(SendRequestAction::class);

        // Act

        $result = $action->execute($data);

        // Assert

        $this->assertTrue($result['rolledBack']);
        $this->assertNotEmpty($result['sentRequestId']);

        $this->executorMock
            ->shouldHaveReceived('execute')
            ->once()
            ->withArgs(function (ExecutionRequestData $sent): bool {
                $this->assertTrue($sent->transactionMode);
                $this->assertSame('POST', $sent->method);
                $this->assertSame('/api/users', $sent->path);

                return true;
            });
    }

    public function test_it_lists_the_connections_a_rollback_did_not_cover(): void
    {
        // Arrange

        $this->fakeExtractedRoutes();

        $this->fakeExecutorResult(
            new ExecutionResultData(
                status: 200,
                headers: ['x-nimbus-transaction-skipped' => 'mysql, pgsql'],
                body: '{}',
                durationMs: 1.0,
            ),
        );

        $data = new SendRequestData(
            route: 'POST api/users',
            body: ['name' => 'Ada'],
        );

        $action = resolve(SendRequestAction::class);

        // Act

        $result = $action->execute($data);

        // Assert

        $this->assertSame(
            ['mysql', 'pgsql'],
            $result['uncoveredConnections'],
        );

        $this->assertArrayNotHasKey(
            'x-nimbus-transaction-skipped',
            $result['headers'],
        );
    }

    public function test_it_drops_the_internal_headers_of_the_agent(): void
    {
        // Arrange

        $this->fakeExtractedRoutes();

        config()->set('nimbus.applications.main.headers', []);

        $data = new SendRequestData(
            route: 'POST api/users',
            headers: [
                'X-Nimbus-Transaction-Mode' => '0',
                'X-Nimbus-Actor' => 'web:1',
                'Accept' => 'application/json',
            ],
        );

        $action = resolve(SendRequestAction::class);

        // Act

        $action->execute($data);

        // Assert

        $this->executorMock
            ->shouldHaveReceived('execute')
            ->once()
            ->withArgs(function (ExecutionRequestData $sent): bool {
                $this->assertSame(['Accept' => 'application/json'], $sent->headers);

                return true;
            });
    }

    public function test_it_returns_the_decoded_body_and_headers(): void
    {
        // Arrange

        $this->fakeExtractedRoutes();

        $data = new SendRequestData(route: 'POST api/users');

        $action = resolve(SendRequestAction::class);

        // Act

        $result = $action->execute($data);

        // Assert

        $this->assertSame(
            ['ok' => true, 'api_token' => 'abc'],
            $result['body'],
        );

        $this->assertSame(
            ['content-type' => 'application/json', 'set-cookie' => 'a=b'],
            $result['headers'],
        );

        $this->assertSame(
            ['date', 'server'],
            $result['omittedHeaders'],
        );
    }

    public function test_it_does_not_wrap_a_read_request(): void
    {
        // Arrange

        $this->fakeExtractedRoutes();

        $data = new SendRequestData(route: 'GET api/users');

        $action = resolve(SendRequestAction::class);

        // Act

        $result = $action->execute($data);

        // Assert

        $this->assertFalse($result['rolledBack']);
    }

    public function test_it_can_allow_committed_writes(): void
    {
        // Arrange

        $this->fakeExtractedRoutes();

        $this->app['config']->set('nimbus.mcp.allow_committed_writes', true);

        $data = new SendRequestData(route: 'POST api/users');

        $action = resolve(SendRequestAction::class);

        // Act

        $result = $action->execute($data);

        // Assert

        $this->assertFalse($result['rolledBack']);
    }

    public function test_it_fills_route_parameters(): void
    {
        // Arrange

        $this->fakeExtractedRoutes();

        $data = new SendRequestData(
            route: 'PUT api/posts/{post}',
            params: ['post' => 7],
        );

        $action = resolve(SendRequestAction::class);

        // Act

        $action->execute($data);

        // Assert

        $this->executorMock
            ->shouldHaveReceived('execute')
            ->once()
            ->withArgs(function (ExecutionRequestData $sent): bool {
                $this->assertSame('/api/posts/7', $sent->path);

                return true;
            });
    }

    public function test_it_truncates_a_long_body(): void
    {
        // Arrange

        $this->app['config']->set('nimbus.mcp.body_limit', 10);

        $this->fakeExecutorResult(
            new ExecutionResultData(
                status: 200,
                headers: [],
                body: str_repeat('a', 50),
                durationMs: 1.0,
            ),
        );

        $data = new SendRequestData(
            method: 'GET',
            uri: '/x',
        );

        $action = resolve(SendRequestAction::class);

        // Act

        $result = $action->execute($data);

        // Assert

        $this->assertSame(
            str_repeat('a', 10),
            $result['body'],
        );

        $this->assertTrue($result['bodyTruncated']);
    }

    public function test_it_reports_the_missing_route_parameters(): void
    {
        // Arrange

        $this->fakeExtractedRoutes();

        $data = new SendRequestData(route: 'PUT api/posts/{post}');

        $action = resolve(SendRequestAction::class);

        // Assert

        $this->expectException(McpToolException::class);

        // Act

        $action->execute($data);
    }

    public function test_it_rejects_a_full_url(): void
    {
        // Arrange

        $data = new SendRequestData(
            method: 'GET',
            uri: 'https://evil.test/x',
        );

        $action = resolve(SendRequestAction::class);

        // Act

        $exception = $this->catchFailure(fn (): array => $action->execute($data));

        // Assert

        $this->assertSame(
            McpToolException::INVALID_INPUT,
            $exception->errorCode,
        );

        $this->executorMock->shouldNotHaveReceived('execute');
    }

    public function test_it_rejects_a_remote_environment(): void
    {
        // Arrange

        $data = new SendRequestData(
            method: 'GET',
            uri: '/x',
            environment: 'staging',
        );

        $action = resolve(SendRequestAction::class);

        // Act

        $exception = $this->catchFailure(fn (): array => $action->execute($data));

        // Assert

        $this->assertSame(
            McpToolException::ENVIRONMENT_NOT_ALLOWED,
            $exception->errorCode,
        );

        $this->executorMock->shouldNotHaveReceived('execute');
    }

    public function test_it_rejects_an_actor_that_does_not_exist(): void
    {
        // Arrange

        $data = new SendRequestData(
            method: 'GET',
            uri: '/x',
            actor: '999',
        );

        $action = resolve(SendRequestAction::class);

        // Assert

        $this->expectException(McpToolException::class);

        // Act

        $action->execute($data);
    }

    public function test_it_does_not_limit_the_rate_when_the_limit_is_zero(): void
    {
        // Arrange

        $this->app['config']->set('nimbus.mcp.rate_limit', 0);

        $data = new SendRequestData(
            method: 'GET',
            uri: '/x',
        );

        $action = resolve(SendRequestAction::class);

        // Act

        $action->execute($data);

        $action->execute($data);

        $action->execute($data);

        // Assert

        $this->executorMock->shouldHaveReceived('execute')->times(3);
    }

    public function test_it_requires_a_route_or_a_method_and_a_uri(): void
    {
        // Arrange

        $action = resolve(SendRequestAction::class);

        // Act

        $exception = $this->catchFailure(fn (): array => $action->execute(new SendRequestData(method: 'GET')));

        // Assert

        $this->assertSame(
            McpToolException::INVALID_INPUT,
            $exception->errorCode,
        );

        $this->assertStringContainsString(
            'Give a `route`, or a `method` and a `uri`.',
            $exception->getMessage(),
        );
    }

    public function test_it_rejects_an_unknown_method(): void
    {
        // Arrange

        $action = resolve(SendRequestAction::class);

        // Act

        $exception = $this->catchFailure(fn (): array => $action->execute(new SendRequestData(method: 'FETCH', uri: '/x')));

        // Assert

        $this->assertStringContainsString(
            'Unknown method `FETCH`.',
            $exception->getMessage(),
        );

        $this->executorMock->shouldNotHaveReceived('execute');
    }

    public function test_it_rejects_a_protocol_relative_uri(): void
    {
        // Arrange

        $action = resolve(SendRequestAction::class);

        // Act

        $exception = $this->catchFailure(fn (): array => $action->execute(new SendRequestData(method: 'GET', uri: '//evil.test/x')));

        // Assert

        $this->assertSame(
            McpToolException::INVALID_INPUT,
            $exception->errorCode,
        );

        $this->executorMock->shouldNotHaveReceived('execute');
    }

    public function test_it_sends_the_root_path_when_the_uri_is_only_slashes(): void
    {
        // Arrange

        $action = resolve(SendRequestAction::class);

        // Act

        $action->execute(new SendRequestData(method: 'GET', uri: '/'));

        // Assert

        $this->executorMock
            ->shouldHaveReceived('execute')
            ->withArgs(function (ExecutionRequestData $sent): bool {
                $this->assertSame('/', $sent->path);

                return true;
            });
    }

    public function test_it_uses_the_current_host_when_the_environment_is_empty(): void
    {
        // Arrange

        $action = resolve(SendRequestAction::class);

        // Act

        $action->execute(new SendRequestData(method: 'GET', uri: '/x', environment: ''));

        // Assert

        $this->executorMock
            ->shouldHaveReceived('execute')
            ->withArgs(function (ExecutionRequestData $sent): bool {
                $this->assertSame('current', $sent->environment);

                return true;
            });
    }

    public function test_it_sends_to_a_remote_environment_through_the_http_executor_when_allowed(): void
    {
        // Arrange

        $this->app['config']->set('nimbus.mcp.allow_remote_environments', true);

        $httpExecutorMock = $this->mock(HttpRequestExecutor::class);

        $httpExecutorMock
            ->shouldReceive('execute')
            ->once()
            ->andReturn(new ExecutionResultData(status: 200, headers: [], body: '{}', durationMs: 1.0));

        $action = resolve(SendRequestAction::class);

        // Act

        $action->execute(new SendRequestData(method: 'GET', uri: '/x', environment: 'staging'));

        // Assert

        $httpExecutorMock->shouldHaveReceived('execute')->once();

        $this->executorMock->shouldNotHaveReceived('execute');
    }

    public function test_it_turns_the_query_values_into_strings(): void
    {
        // Arrange

        $action = resolve(SendRequestAction::class);

        // Act

        $action->execute(new SendRequestData(
            method: 'GET',
            uri: '/x',
            query: [
                'page' => 2,
                'active' => true,
                'ids' => [1, 2],
                'nested' => [['a' => 1]],
            ],
        ));

        // Assert

        $this->executorMock
            ->shouldHaveReceived('execute')
            ->withArgs(function (ExecutionRequestData $sent): bool {
                $this->assertSame(
                    [
                        'page' => '2',
                        'active' => '1',
                        'ids' => ['1', '2'],
                        'nested' => ['{"a":1}'],
                    ],
                    $sent->query,
                );

                return true;
            });
    }

    public function test_it_sends_the_actor_header_when_the_actor_exists(): void
    {
        // Arrange

        $userProviderMock = $this->createMock(UserProvider::class);

        $userProviderMock->method('retrieveById')->willReturn(new \stdClass);

        $guardMock = new class($userProviderMock) implements Guard
        {
            public function __construct(private UserProvider $provider) {}

            public function getProvider(): UserProvider
            {
                return $this->provider;
            }

            public function check(): bool
            {
                return false;
            }

            public function guest(): bool
            {
                return true;
            }

            public function user(): null
            {
                return null;
            }

            public function id(): null
            {
                return null;
            }

            public function validate(array $credentials = []): bool
            {
                return false;
            }

            public function hasUser(): bool
            {
                return false;
            }

            public function setUser(Authenticatable $user): void {}
        };

        Auth::shouldReceive('guard')->andReturn($guardMock);

        $action = resolve(SendRequestAction::class);

        // Act

        $action->execute(new SendRequestData(method: 'GET', uri: '/x', actor: '5'));

        // Assert

        $this->executorMock
            ->shouldHaveReceived('execute')
            ->withArgs(function (ExecutionRequestData $sent): bool {
                $this->assertArrayHasKey(NimbusHeaderEnum::Actor->value, $sent->headers);

                return true;
            });
    }

    public function test_it_sends_the_root_path_when_only_an_optional_parameter_is_left_empty(): void
    {
        // Arrange

        $route = new ExtractedRoute(
            new Endpoint('v1', 'home', '/{page?}'),
            ['GET'],
            Schema::empty(),
        );

        config()->set('nimbus.applications', ['main' => config('nimbus.applications.main')]);

        $this->mock(ExtractRoutesAction::class)
            ->shouldReceive('execute')
            ->andReturn(new ExtractedRoutesCollection([$route]));

        $action = resolve(SendRequestAction::class);

        // Act

        $action->execute(new SendRequestData(route: 'GET {page?}'));

        // Assert

        $this->executorMock
            ->shouldHaveReceived('execute')
            ->withArgs(function (ExecutionRequestData $sent): bool {
                $this->assertSame('/', $sent->path);

                return true;
            });
    }

    public function test_it_summarizes_the_insight_of_the_application(): void
    {
        // Arrange

        $this->fakeExecutorResult(
            new ExecutionResultData(
                status: 200,
                headers: [],
                body: '{}',
                durationMs: 1.0,
                insight: new InsightData(
                    queries: new QueriesResult([]),
                    logs: new LogsResult([]),
                    gates: new GatesResult([]),
                    exception: new ExceptionResult(null),
                ),
            ),
        );

        $action = resolve(SendRequestAction::class);

        // Act

        $result = $action->execute(new SendRequestData(method: 'GET', uri: '/x'));

        // Assert

        $this->assertSame(
            ['exception' => null, 'deniedChecks' => 0, 'queryCount' => 0],
            $result['insight'],
        );
    }

    public function test_it_limits_the_rate(): void
    {
        // Arrange

        $this->app['config']->set('nimbus.mcp.rate_limit', 2);

        $data = new SendRequestData(
            method: 'GET',
            uri: '/x',
        );

        $action = resolve(SendRequestAction::class);

        $action->execute($data);

        $action->execute($data);

        // Act

        $exception = $this->catchFailure(fn (): array => $action->execute($data));

        // Assert

        $this->assertSame(
            McpToolException::RATE_LIMITED,
            $exception->errorCode,
        );
    }

    public function test_it_sends_the_fixed_global_headers_of_the_application(): void
    {
        // Arrange

        $this->fakeExtractedRoutes();

        config()->set('nimbus.applications.main.headers', [
            'X-App' => 'main',
            'X-Request-Id' => '$uuid',
        ]);

        $data = new SendRequestData(
            route: 'POST api/users',
            headers: ['X-App' => 'agent'],
        );

        $action = resolve(SendRequestAction::class);

        // Act

        $action->execute($data);

        // Assert

        $this->executorMock
            ->shouldHaveReceived('execute')
            ->once()
            ->withArgs(function (ExecutionRequestData $sent): bool {
                $this->assertSame(['X-App' => 'agent'], $sent->headers);

                return true;
            });
    }

    public function test_it_warns_about_a_generated_header_the_agent_did_not_send(): void
    {
        // Arrange

        $this->fakeExtractedRoutes();

        config()->set('nimbus.applications.main.headers', [
            'X-Request-Id' => '$uuid',
            'X-Contact' => '$email',
        ]);

        $data = new SendRequestData(
            route: 'POST api/users',
            headers: ['x-contact' => 'ada@example.com'],
        );

        $action = resolve(SendRequestAction::class);

        // Act

        $result = $action->execute($data);

        // Assert

        $this->assertSame(
            ['The header `X-Request-Id` needs a generated value of type `$uuid`. It was not sent. Pass it in `headers`.'],
            $result['warnings'],
        );

        $this->executorMock
            ->shouldHaveReceived('execute')
            ->once();
    }

    public function test_it_runs_in_the_application_that_is_asked_for(): void
    {
        // Arrange

        $this->fakeExtractedRoutes();

        config()->set('nimbus.applications', [
            'main' => ['headers' => ['X-App' => 'main']],
            'other' => ['headers' => ['X-App' => 'other']],
        ]);

        $data = new SendRequestData(
            route: 'POST api/users',
            application: 'other',
        );

        $action = resolve(SendRequestAction::class);

        // Act

        $result = $action->execute($data);

        // Assert

        $this->assertSame(
            'other',
            $result['application'],
        );

        $this->executorMock
            ->shouldHaveReceived('execute')
            ->once()
            ->withArgs(function (ExecutionRequestData $sent): bool {
                $this->assertSame(['X-App' => 'other'], $sent->headers);

                return true;
            });

        $this->assertSame(
            'main',
            resolve(ActiveApplicationResolver::class)->getActiveApplicationKey(),
        );
    }

    public function test_it_fails_when_a_route_exists_in_several_applications(): void
    {
        // Arrange

        $this->fakeExtractedRoutes();

        config()->set('nimbus.applications', [
            'main' => [],
            'other' => [],
        ]);

        $action = resolve(SendRequestAction::class);

        // Act

        $exception = $this->catchFailure(
            fn (): array => $action->execute(new SendRequestData(route: 'POST api/users')),
        );

        // Assert

        $this->assertSame(
            McpToolException::ROUTE_AMBIGUOUS,
            $exception->errorCode,
        );

        $this->executorMock->shouldNotHaveReceived('execute');
    }

    /*
     * Mocks.
     */

    private function fakeExecutorResult(ExecutionResultData $result): void
    {
        $this->executorMock = $this->mock(RequestExecutor::class);

        $this->executorMock
            ->shouldReceive('execute')
            ->andReturn($result);
    }

    /*
     * Helpers.
     */

    private function catchFailure(callable $callback): McpToolException
    {
        try {
            $callback();
        } catch (McpToolException $mcpToolException) {
            return $mcpToolException;
        }

        $this->fail('The action must fail.');
    }
}
