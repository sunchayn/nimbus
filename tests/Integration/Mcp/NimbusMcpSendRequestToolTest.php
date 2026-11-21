<?php

declare(strict_types=1);

namespace Sunchayn\Nimbus\Tests\Integration\Mcp;

use Generator;
use Illuminate\Support\Facades\RateLimiter;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use Sunchayn\Nimbus\Modules\Mcp\Actions\SendRequestAction;
use Sunchayn\Nimbus\Modules\Mcp\Contracts\RequestExecutor;
use Sunchayn\Nimbus\Modules\Mcp\DataTransferObjects\ExecutionRequestData;
use Sunchayn\Nimbus\Modules\Mcp\DataTransferObjects\ExecutionResultData;
use Sunchayn\Nimbus\Modules\Mcp\DataTransferObjects\SendRequestData;
use Sunchayn\Nimbus\Modules\Mcp\Exceptions\McpToolException;
use Sunchayn\Nimbus\Modules\Mcp\Server\Tools\SendRequestTool;
use Sunchayn\Nimbus\Tests\App\Modules\Mcp\Concerns\FakesExtractedRoutes;
use Sunchayn\Nimbus\Tests\Concerns\InteractsWithMcpServer;
use Sunchayn\Nimbus\Tests\Concerns\UsesTemporaryBasePath;
use Sunchayn\Nimbus\Tests\TestCase;

#[CoversClass(SendRequestTool::class)]
class NimbusMcpSendRequestToolTest extends TestCase
{
    use FakesExtractedRoutes;
    use InteractsWithMcpServer;
    use UsesTemporaryBasePath;

    protected function setUp(): void
    {
        parent::setUp();

        $this->useTemporaryBasePath();
    }

    public function test_it_passes_the_request_options_to_the_action(): void
    {
        // Arrange

        $sendRequestActionMock = $this->mock(SendRequestAction::class);

        // Anticipate

        $sendRequestActionMock
            ->shouldReceive('execute')
            ->andReturn(
                [
                    'sentRequestId' => 'run-1',
                    'status' => 201,
                ],
            );

        // Act

        $this->callTool(
            name: 'nimbus_send_request',
            arguments: [
                'route' => 'PUT api/posts/{post}',
                'params' => [
                    'post' => 7,
                ],
                'query' => [
                    'draft' => '1',
                ],
                'headers' => [
                    'Accept' => 'application/json',
                ],
                'body' => [
                    'title' => 'Hello',
                ],
                'actor' => '5',
            ],
        );

        // Assert

        $sendRequestActionMock
            ->shouldHaveReceived('execute')
            ->once()
            ->withArgs(function (SendRequestData $data): bool {
                $this->assertSame(
                    'PUT api/posts/{post}',
                    $data->route,
                );

                $this->assertSame(
                    ['post' => 7],
                    $data->params,
                );

                $this->assertSame(
                    ['draft' => '1'],
                    $data->query,
                );

                $this->assertSame(
                    ['Accept' => 'application/json'],
                    $data->headers,
                );

                $this->assertSame(
                    ['title' => 'Hello'],
                    $data->body,
                );

                $this->assertSame(
                    '5',
                    $data->actor,
                );

                $this->assertSame(
                    'current',
                    $data->environment,
                );

                return true;
            });
    }

    public function test_it_passes_a_raw_method_and_uri_to_the_action(): void
    {
        // Arrange

        $sendRequestActionMock = $this->mock(SendRequestAction::class);

        // Anticipate

        $sendRequestActionMock
            ->shouldReceive('execute')
            ->andReturn(
                [
                    'sentRequestId' => 'run-1',
                    'status' => 200,
                ],
            );

        // Act

        $this->callTool(
            name: 'nimbus_send_request',
            arguments: [
                'method' => 'GET',
                'uri' => '/api/users',
                'environment' => 'staging',
            ],
        );

        // Assert

        $sendRequestActionMock
            ->shouldHaveReceived('execute')
            ->once()
            ->withArgs(function (SendRequestData $data): bool {
                $this->assertNull($data->route);

                $this->assertSame(
                    'GET',
                    $data->method,
                );

                $this->assertSame(
                    '/api/users',
                    $data->uri,
                );

                $this->assertSame(
                    'staging',
                    $data->environment,
                );

                return true;
            });
    }

    /**
     * @param  array<string, mixed>|string  $body
     */
    #[DataProvider('bodies')]
    public function test_it_passes_the_body_to_the_action_as_given(array|string $body): void
    {
        // Arrange

        $sendRequestActionMock = $this->mock(SendRequestAction::class);

        // Anticipate

        $sendRequestActionMock
            ->shouldReceive('execute')
            ->andReturn(
                [
                    'sentRequestId' => 'run-1',
                    'status' => 200,
                ],
            );

        // Act

        $response = $this->callTool(
            name: 'nimbus_send_request',
            arguments: [
                'route' => 'POST api/users',
                'body' => $body,
            ],
        );

        // Assert

        $response->assertOk();

        $sendRequestActionMock
            ->shouldHaveReceived('execute')
            ->once()
            ->withArgs(function (SendRequestData $data) use ($body): bool {
                $this->assertSame(
                    $body,
                    $data->body,
                );

                return true;
            });
    }

    public static function bodies(): Generator
    {
        yield 'a string body' => [
            'body' => 'plain text payload',
        ];

        yield 'an object body' => [
            'body' => ['name' => 'Ada'],
        ];
    }

    public function test_it_returns_the_response_of_the_application(): void
    {
        // Arrange

        $sendRequestActionMock = $this->mock(SendRequestAction::class);

        // Anticipate

        $sendRequestActionMock
            ->shouldReceive('execute')
            ->andReturn(
                [
                    'sentRequestId' => 'run-1',
                    'status' => 201,
                    'body' => [
                        'id' => 'created-user',
                    ],
                ],
            );

        // Act

        $response = $this->callTool(
            name: 'nimbus_send_request',
            arguments: [
                'route' => 'POST api/users',
            ],
        );

        // Assert

        $response
            ->assertOk()
            ->assertSee('created-user');
    }

    public function test_it_reports_a_failure_as_an_error(): void
    {
        // Arrange

        $sendRequestActionMock = $this->mock(SendRequestAction::class);

        // Anticipate

        $sendRequestActionMock
            ->shouldReceive('execute')
            ->andThrow(McpToolException::becauseRateIsLimited(seconds: 30));

        // Act

        $response = $this->callTool(
            name: 'nimbus_send_request',
            arguments: [
                'route' => 'POST api/users',
            ],
        );

        // Assert

        $response
            ->assertHasErrors()
            ->assertSee('RATE_LIMITED');
    }

    public function test_it_integrates(): void
    {
        // Arrange

        RateLimiter::clear(SendRequestAction::RATE_LIMIT_KEY);

        $this->fakeExtractedRoutes();

        // The executor is the only fake, so the real action runs and the application is not called.
        $requestExecutorMock = $this->mock(RequestExecutor::class);

        $executionResult = new ExecutionResultData(
            status: 201,
            headers: [
                'content-type' => 'application/json',
            ],
            body: '{"id":"created-user"}',
            durationMs: 3.0,
        );

        // Anticipate

        $requestExecutorMock
            ->shouldReceive('execute')
            ->andReturn($executionResult);

        // Act

        $response = $this->callTool(
            name: 'nimbus_send_request',
            arguments: [
                'route' => 'POST api/users',
                'body' => [
                    'name' => 'Ada',
                ],
            ],
        );

        // Assert

        $response
            ->assertOk()
            ->assertSee('created-user');

        $requestExecutorMock
            ->shouldHaveReceived('execute')
            ->once()
            ->withArgs(function (ExecutionRequestData $data): bool {
                $this->assertSame(
                    'POST',
                    $data->method,
                );

                $this->assertSame(
                    '/api/users',
                    $data->path,
                );

                $this->assertSame(
                    ['name' => 'Ada'],
                    $data->body,
                );

                return true;
            });
    }
}
