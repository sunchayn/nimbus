<?php

declare(strict_types=1);

namespace Sunchayn\Nimbus\Tests\Integration\Mcp;

use Generator;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use Sunchayn\Nimbus\Modules\Mcp\Actions\SaveRequestAction;
use Sunchayn\Nimbus\Modules\Mcp\DataTransferObjects\SaveRequestData;
use Sunchayn\Nimbus\Modules\Mcp\Exceptions\McpToolException;
use Sunchayn\Nimbus\Modules\Mcp\Server\Tools\SaveRequestTool;
use Sunchayn\Nimbus\Tests\Concerns\InteractsWithMcpServer;
use Sunchayn\Nimbus\Tests\Concerns\UsesTemporaryWorkspace;
use Sunchayn\Nimbus\Tests\TestCase;

#[CoversClass(SaveRequestTool::class)]
class NimbusMcpSaveRequestToolTest extends TestCase
{
    use InteractsWithMcpServer;
    use UsesTemporaryWorkspace;

    protected function setUp(): void
    {
        parent::setUp();

        $this->enableWorkspace();

        config()->set('nimbus.mcp.level', 'write');
    }

    protected function tearDown(): void
    {
        $this->tearDownWorkspace();

        parent::tearDown();
    }

    public function test_it_passes_the_request_to_the_save_request_action(): void
    {
        // Arrange

        $saveRequestActionMock = $this->mock(SaveRequestAction::class);

        // Anticipate

        $saveRequestActionMock
            ->shouldReceive('execute')
            ->andReturn(
                [
                    'id' => 'request-1',
                    'file' => 'requests/users/create-user.request.json',
                ],
            );

        // Act

        $this->callTool(
            name: 'nimbus_save_request',
            arguments: [
                'name' => 'Create user',
                'method' => 'post',
                'endpoint' => '/api/users',
                'body' => [
                    'name' => '{{name}}',
                ],
                'folder' => 'users',
            ],
        );

        // Assert

        $saveRequestActionMock
            ->shouldHaveReceived('execute')
            ->once()
            ->withArgs(function (SaveRequestData $data): bool {
                $this->assertSame(
                    'Create user',
                    $data->name,
                );

                $this->assertSame(
                    'post',
                    $data->method,
                );

                $this->assertSame(
                    '/api/users',
                    $data->endpoint,
                );

                $this->assertSame(
                    ['name' => '{{name}}'],
                    $data->body,
                );

                $this->assertSame(
                    'users',
                    $data->folder,
                );

                return true;
            });
    }

    /**
     * @param  array<string, mixed>|string  $body
     */
    #[DataProvider('bodies')]
    public function test_it_passes_the_body_to_the_save_request_action_as_given(array|string $body): void
    {
        // Arrange

        $saveRequestActionMock = $this->mock(SaveRequestAction::class);

        // Anticipate

        $saveRequestActionMock
            ->shouldReceive('execute')
            ->andReturn(
                [
                    'id' => 'request-1',
                    'file' => 'requests/create-user.request.json',
                ],
            );

        // Act

        $response = $this->callTool(
            name: 'nimbus_save_request',
            arguments: [
                'name' => 'Create user',
                'method' => 'POST',
                'endpoint' => '/api/users',
                'body' => $body,
            ],
        );

        // Assert

        $response->assertOk();

        $saveRequestActionMock
            ->shouldHaveReceived('execute')
            ->once()
            ->withArgs(function (SaveRequestData $data) use ($body): bool {
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
            'body' => 'name=Ada',
        ];

        yield 'an object body' => [
            'body' => ['name' => 'Ada'],
        ];
    }

    public function test_it_returns_the_saved_file(): void
    {
        // Arrange

        $saveRequestActionMock = $this->mock(SaveRequestAction::class);

        // Anticipate

        $saveRequestActionMock
            ->shouldReceive('execute')
            ->andReturn(
                [
                    'id' => 'request-1',
                    'file' => 'requests/users/create-user.request.json',
                ],
            );

        // Act

        $response = $this->callTool(
            name: 'nimbus_save_request',
            arguments: [
                'name' => 'Create user',
                'method' => 'POST',
                'endpoint' => '/api/users',
            ],
        );

        // Assert

        $response
            ->assertOk()
            ->assertSee('create-user.request.json');
    }

    public function test_it_refuses_to_save_a_credential_header(): void
    {
        // Arrange

        $saveRequestActionMock = $this->mock(SaveRequestAction::class);

        $credentialHeaderException = McpToolException::becauseInputIsInvalid(
            message: 'The `Authorization` header cannot be saved.',
            hint: 'Credentials are never written.',
        );

        // Anticipate

        $saveRequestActionMock
            ->shouldReceive('execute')
            ->andThrow($credentialHeaderException);

        // Act

        $response = $this->callTool(
            name: 'nimbus_save_request',
            arguments: [
                'name' => 'Secret',
                'method' => 'GET',
                'endpoint' => '/api/me',
                'headers' => [
                    'Authorization' => 'Bearer abc',
                ],
            ],
        );

        // Assert

        $response
            ->assertHasErrors()
            ->assertSee('INVALID_INPUT');
    }

    public function test_it_integrates(): void
    {
        // Act

        $response = $this->callTool(
            name: 'nimbus_save_request',
            arguments: [
                'name' => 'Create user',
                'method' => 'POST',
                'endpoint' => '/api/users',
                'body' => [
                    'name' => '{{name}}',
                ],
                'folder' => 'users',
            ],
        );

        // Assert

        $response
            ->assertOk()
            ->assertSee('create-user.request.json');

        $this->assertFileExists("{$this->workspacePath}/requests/users/create-user.request.json");
    }
}
