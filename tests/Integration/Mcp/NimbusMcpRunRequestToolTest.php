<?php

declare(strict_types=1);

namespace Sunchayn\Nimbus\Tests\Integration\Mcp;

use Illuminate\Support\Facades\RateLimiter;
use PHPUnit\Framework\Attributes\CoversClass;
use Sunchayn\Nimbus\Modules\Mcp\Actions\RunSavedRequestAction;
use Sunchayn\Nimbus\Modules\Mcp\Actions\SendRequestAction;
use Sunchayn\Nimbus\Modules\Mcp\Contracts\RequestExecutor;
use Sunchayn\Nimbus\Modules\Mcp\DataTransferObjects\ExecutionRequestData;
use Sunchayn\Nimbus\Modules\Mcp\DataTransferObjects\ExecutionResultData;
use Sunchayn\Nimbus\Modules\Mcp\Exceptions\McpToolException;
use Sunchayn\Nimbus\Modules\Mcp\Server\Tools\RunRequestTool;
use Sunchayn\Nimbus\Modules\Workspace\DataTransferObjects\SavedRequest;
use Sunchayn\Nimbus\Tests\Concerns\InteractsWithMcpServer;
use Sunchayn\Nimbus\Tests\Concerns\UsesTemporaryBasePath;
use Sunchayn\Nimbus\Tests\Concerns\UsesTemporaryWorkspace;
use Sunchayn\Nimbus\Tests\TestCase;

#[CoversClass(RunRequestTool::class)]
class NimbusMcpRunRequestToolTest extends TestCase
{
    use InteractsWithMcpServer;
    use UsesTemporaryBasePath;
    use UsesTemporaryWorkspace;

    protected function setUp(): void
    {
        parent::setUp();

        $this->useTemporaryBasePath();

        $this->enableWorkspace();
    }

    protected function tearDown(): void
    {
        $this->tearDownWorkspace();

        parent::tearDown();
    }

    public function test_it_passes_the_variables_to_the_run_saved_request_action(): void
    {
        // Arrange

        $runSavedRequestActionMock = $this->mock(RunSavedRequestAction::class);

        // Anticipate

        $runSavedRequestActionMock
            ->shouldReceive('execute')
            ->andReturn(
                [
                    'status' => 201,
                ],
            );

        // Act

        $this->callTool(
            name: 'nimbus_run_request',
            arguments: [
                'saved_request_id' => 'create-user.request.json',
                'variables' => [
                    'id' => 7,
                    'name' => 'Ada',
                ],
            ],
        );

        // Assert

        $runSavedRequestActionMock
            ->shouldHaveReceived('execute')
            ->once()
            ->withArgs(function (string $savedRequestId, array $variables): bool {
                $this->assertSame(
                    'create-user.request.json',
                    $savedRequestId,
                );

                $this->assertSame(
                    ['id' => 7, 'name' => 'Ada'],
                    $variables,
                );

                return true;
            });
    }

    public function test_it_returns_the_result_of_the_run(): void
    {
        // Arrange

        $runSavedRequestActionMock = $this->mock(RunSavedRequestAction::class);

        // Anticipate

        $runSavedRequestActionMock
            ->shouldReceive('execute')
            ->andReturn(
                [
                    'status' => 201,
                ],
            );

        // Act

        $response = $this->callTool(
            name: 'nimbus_run_request',
            arguments: [
                'saved_request_id' => 'create-user.request.json',
            ],
        );

        // Assert

        $response
            ->assertOk()
            ->assertSee('201');
    }

    public function test_it_asks_for_missing_variables(): void
    {
        // Arrange

        $runSavedRequestActionMock = $this->mock(RunSavedRequestAction::class);

        $missingVariablesException = McpToolException::becauseInputIsInvalid(
            message: 'The saved request needs values for id.',
            hint: 'Pass them in `variables`.',
        );

        // Anticipate

        $runSavedRequestActionMock
            ->shouldReceive('execute')
            ->andThrow($missingVariablesException);

        // Act

        $response = $this->callTool(
            name: 'nimbus_run_request',
            arguments: [
                'saved_request_id' => 'show-user.request.json',
            ],
        );

        // Assert

        $response
            ->assertHasErrors()
            ->assertSee('id');
    }

    public function test_it_integrates(): void
    {
        // Arrange

        RateLimiter::clear(SendRequestAction::RATE_LIMIT_KEY);

        $savedRequest = SavedRequest::fromArray([
            'id' => 'request-1',
            'name' => 'Create user',
            'method' => 'POST',
            'endpoint' => '/api/users/{{id}}',
            'body' => [
                'type' => 'json',
                'content' => '{ "name": "{{name}}" }',
            ],
        ]);

        $this->saveRequest(
            savedRequest: $savedRequest,
            hash: null,
        );

        // The executor is the only fake, so the real actions run and the application is not called.
        $requestExecutorMock = $this->mock(RequestExecutor::class);

        $executionResult = new ExecutionResultData(
            status: 201,
            headers: [],
            body: '{"id":"created-user"}',
            durationMs: 3.0,
        );

        // Anticipate

        $requestExecutorMock
            ->shouldReceive('execute')
            ->andReturn($executionResult);

        // Act

        $response = $this->callTool(
            name: 'nimbus_run_request',
            arguments: [
                'saved_request_id' => 'request-1',
                'variables' => [
                    'id' => 7,
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
                    '/api/users/7',
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
