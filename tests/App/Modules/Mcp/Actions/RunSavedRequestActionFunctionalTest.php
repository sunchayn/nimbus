<?php

declare(strict_types=1);

namespace Sunchayn\Nimbus\Tests\App\Modules\Mcp\Actions;

use PHPUnit\Framework\Attributes\CoversClass;
use Sunchayn\Nimbus\Modules\Mcp\Actions\RunSavedRequestAction;
use Sunchayn\Nimbus\Modules\Mcp\Actions\SendRequestAction;
use Sunchayn\Nimbus\Modules\Mcp\DataTransferObjects\SendRequestData;
use Sunchayn\Nimbus\Modules\Mcp\Exceptions\McpToolException;
use Sunchayn\Nimbus\Modules\Workspace\DataTransferObjects\SavedRequest;
use Sunchayn\Nimbus\Modules\Workspace\Exceptions\WorkspaceException;
use Sunchayn\Nimbus\Tests\Concerns\UsesTemporaryBasePath;
use Sunchayn\Nimbus\Tests\Concerns\UsesTemporaryWorkspace;
use Sunchayn\Nimbus\Tests\TestCase;

#[CoversClass(RunSavedRequestAction::class)]
class RunSavedRequestActionFunctionalTest extends TestCase
{
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

    public function test_it_sends_the_request_with_its_variables_filled(): void
    {
        // Arrange

        $this->saveRunnable([
            'endpoint' => '/api/users/{{id}}',
            'query' => [['key' => 'page', 'value' => '{{page}}', 'enabled' => true]],
            'body' => ['type' => 'json', 'content' => '{"name":"{{name}}"}'],
        ]);

        $sendRequestActionMock = $this->mock(SendRequestAction::class);

        // Anticipate

        $sendRequestActionMock
            ->shouldReceive('execute')
            ->andReturn(['status' => 201]);

        $action = resolve(RunSavedRequestAction::class);

        // Act

        $result = $action->execute(
            savedRequestId: 'runnable',
            variables: ['id' => 7, 'page' => 2, 'name' => 'Ada'],
        );

        // Assert

        $this->assertSame(
            ['status' => 201],
            $result,
        );

        $sendRequestActionMock
            ->shouldHaveReceived('execute')
            ->once()
            ->withArgs(function (SendRequestData $data): bool {
                $this->assertSame(
                    'POST',
                    $data->method,
                );

                $this->assertSame(
                    '/api/users/7',
                    $data->uri,
                );

                $this->assertSame(
                    ['page' => '2'],
                    $data->query,
                );

                $this->assertSame(
                    ['name' => 'Ada'],
                    $data->body,
                );

                return true;
            });
    }

    public function test_it_skips_the_disabled_and_unnamed_parameters(): void
    {
        // Arrange

        $this->saveRunnable([
            'headers' => [
                ['key' => 'Accept', 'value' => 'application/json', 'enabled' => true],
                ['key' => 'X-Off', 'value' => 'off', 'enabled' => false],
                ['key' => '', 'value' => 'nameless', 'enabled' => true],
            ],
        ]);

        $sendRequestActionMock = $this->mock(SendRequestAction::class);

        // Anticipate

        $sendRequestActionMock
            ->shouldReceive('execute')
            ->andReturn([]);

        $action = resolve(RunSavedRequestAction::class);

        // Act

        $action->execute(savedRequestId: 'runnable');

        // Assert

        $sendRequestActionMock
            ->shouldHaveReceived('execute')
            ->once()
            ->withArgs(function (SendRequestData $data): bool {
                $this->assertSame(
                    ['Accept' => 'application/json'],
                    $data->headers,
                );

                return true;
            });
    }

    public function test_it_sends_a_bearer_token_as_a_header(): void
    {
        // Arrange

        $this->saveRunnable([
            'authorization' => ['type' => 'bearer', 'value' => '{{token}}'],
        ]);

        $sendRequestActionMock = $this->mock(SendRequestAction::class);

        // Anticipate

        $sendRequestActionMock
            ->shouldReceive('execute')
            ->andReturn([]);

        $action = resolve(RunSavedRequestAction::class);

        // Act

        $action->execute(
            savedRequestId: 'runnable',
            variables: ['token' => 'abc'],
        );

        // Assert

        $sendRequestActionMock
            ->shouldHaveReceived('execute')
            ->once()
            ->withArgs(function (SendRequestData $data): bool {
                $this->assertSame(
                    'Bearer abc',
                    $data->headers['Authorization'],
                );

                return true;
            });
    }

    public function test_it_sends_the_basic_credentials_as_a_header(): void
    {
        // Arrange

        $this->saveRunnable([
            'authorization' => ['type' => 'basic', 'value' => ['username' => 'ada', 'password' => '{{password}}']],
        ]);

        $sendRequestActionMock = $this->mock(SendRequestAction::class);

        // Anticipate

        $sendRequestActionMock
            ->shouldReceive('execute')
            ->andReturn([]);

        $action = resolve(RunSavedRequestAction::class);

        // Act

        $action->execute(
            savedRequestId: 'runnable',
            variables: ['password' => 'secret'],
        );

        // Assert

        $sendRequestActionMock
            ->shouldHaveReceived('execute')
            ->once()
            ->withArgs(function (SendRequestData $data): bool {
                $this->assertSame(
                    'Basic '.base64_encode('ada:secret'),
                    $data->headers['Authorization'],
                );

                return true;
            });
    }

    public function test_it_sends_an_impersonated_user_as_the_actor(): void
    {
        // Arrange

        $this->saveRunnable([
            'authorization' => ['type' => 'impersonate', 'value' => '{{user}}'],
        ]);

        $sendRequestActionMock = $this->mock(SendRequestAction::class);

        // Anticipate

        $sendRequestActionMock
            ->shouldReceive('execute')
            ->andReturn([]);

        $action = resolve(RunSavedRequestAction::class);

        // Act

        $action->execute(
            savedRequestId: 'runnable',
            variables: ['user' => '5'],
        );

        // Assert

        $sendRequestActionMock
            ->shouldHaveReceived('execute')
            ->once()
            ->withArgs(function (SendRequestData $data): bool {
                $this->assertSame(
                    '5',
                    $data->actor,
                );

                return true;
            });
    }

    public function test_it_sends_a_plain_text_body_as_it_is(): void
    {
        // Arrange

        $this->saveRunnable([
            'body' => ['type' => 'plain-text', 'content' => 'hello {{name}}'],
        ]);

        $sendRequestActionMock = $this->mock(SendRequestAction::class);

        // Anticipate

        $sendRequestActionMock
            ->shouldReceive('execute')
            ->andReturn([]);

        $action = resolve(RunSavedRequestAction::class);

        // Act

        $action->execute(
            savedRequestId: 'runnable',
            variables: ['name' => 'Ada'],
        );

        // Assert

        $sendRequestActionMock
            ->shouldHaveReceived('execute')
            ->once()
            ->withArgs(function (SendRequestData $data): bool {
                $this->assertSame(
                    'hello Ada',
                    $data->body,
                );

                return true;
            });
    }

    public function test_it_asks_for_the_missing_variables(): void
    {
        // Arrange

        $this->saveRunnable([
            'endpoint' => '/api/users/{{id}}',
        ]);

        $sendRequestActionSpy = $this->spy(SendRequestAction::class);

        $action = resolve(RunSavedRequestAction::class);

        // Act

        $exception = null;

        try {
            $action->execute(savedRequestId: 'runnable');
        } catch (McpToolException $mcpToolException) {
            $exception = $mcpToolException;
        }

        // Assert

        $this->assertInstanceOf(
            McpToolException::class,
            $exception,
        );

        $this->assertSame(
            McpToolException::INVALID_INPUT,
            $exception->errorCode,
        );

        $this->assertStringContainsString(
            'id',
            $exception->getMessage(),
        );

        $sendRequestActionSpy->shouldNotHaveReceived('execute');
    }

    public function test_it_rejects_a_json_body_that_is_not_valid_once_filled(): void
    {
        // Arrange

        $this->saveRunnable([
            'body' => ['type' => 'json', 'content' => '{"name": {{name}}'],
        ]);

        $this->spy(SendRequestAction::class);

        $action = resolve(RunSavedRequestAction::class);

        // Assert

        $this->expectException(McpToolException::class);
        $this->expectExceptionMessage('The saved JSON body is not valid');

        // Act

        $action->execute(
            savedRequestId: 'runnable',
            variables: ['name' => '"Ada"'],
        );
    }

    public function test_it_fails_for_an_unknown_request(): void
    {
        // Arrange

        $this->spy(SendRequestAction::class);

        $action = resolve(RunSavedRequestAction::class);

        // Assert

        $this->expectException(WorkspaceException::class);

        // Act

        $action->execute(savedRequestId: 'unknown');
    }

    /*
     * Seeders.
     */

    /**
     * @param  array<string, mixed>  $overrides
     */
    private function saveRunnable(array $overrides = []): void
    {
        $savedRequest = SavedRequest::fromArray([
            'id' => 'runnable',
            'name' => 'Runnable',
            'method' => 'POST',
            'endpoint' => '/api/users',
            ...$overrides,
        ]);

        $this->saveRequest(
            savedRequest: $savedRequest,
            hash: null,
        );
    }
}
