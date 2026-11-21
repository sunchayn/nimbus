<?php

declare(strict_types=1);

namespace Sunchayn\Nimbus\Tests\Integration\Mcp;

use PHPUnit\Framework\Attributes\CoversClass;
use Sunchayn\Nimbus\Modules\Mcp\Actions\ExplainResponseAction;
use Sunchayn\Nimbus\Modules\Mcp\Exceptions\McpToolException;
use Sunchayn\Nimbus\Modules\Mcp\Server\Tools\ExplainResponseTool;
use Sunchayn\Nimbus\Modules\Mcp\Services\SentRequestStore;
use Sunchayn\Nimbus\Tests\Concerns\InteractsWithMcpServer;
use Sunchayn\Nimbus\Tests\Concerns\UsesTemporaryBasePath;
use Sunchayn\Nimbus\Tests\TestCase;

#[CoversClass(ExplainResponseTool::class)]
class NimbusMcpExplainResponseToolTest extends TestCase
{
    use InteractsWithMcpServer;
    use UsesTemporaryBasePath;

    protected function setUp(): void
    {
        parent::setUp();

        $this->useTemporaryBasePath();
    }

    public function test_it_passes_the_sent_request_id_to_the_action(): void
    {
        // Arrange

        $explainResponseActionMock = $this->mock(ExplainResponseAction::class);

        // Anticipate

        $explainResponseActionMock
            ->shouldReceive('execute')
            ->andReturn(
                [
                    'sentRequestId' => 'sent-1',
                    'status' => 200,
                ],
            );

        // Act

        $this->callTool(
            name: 'nimbus_explain_response',
            arguments: [
                'sent_request_id' => 'sent-1',
            ],
        );

        // Assert

        $explainResponseActionMock
            ->shouldHaveReceived('execute')
            ->once()
            ->with('sent-1');
    }

    public function test_it_returns_the_explanation(): void
    {
        // Arrange

        $explainResponseActionMock = $this->mock(ExplainResponseAction::class);

        // Anticipate

        $explainResponseActionMock
            ->shouldReceive('execute')
            ->andReturn(
                [
                    'sentRequestId' => 'sent-1',
                    'status' => 422,
                    'likelyCause' => 'Validation failed on `email`.',
                    'evidence' => [],
                ],
            );

        // Act

        $response = $this->callTool(
            name: 'nimbus_explain_response',
            arguments: [
                'sent_request_id' => 'sent-1',
            ],
        );

        // Assert

        $response
            ->assertOk()
            ->assertSee('Validation failed on');
    }

    public function test_it_reports_a_failure_as_an_error(): void
    {
        // Arrange

        $explainResponseActionMock = $this->mock(ExplainResponseAction::class);

        // Anticipate

        $explainResponseActionMock
            ->shouldReceive('execute')
            ->andThrow(McpToolException::becauseSentRequestIsNotFound(sentRequestId: 'missing'));

        // Act

        $response = $this->callTool(
            name: 'nimbus_explain_response',
            arguments: [
                'sent_request_id' => 'missing',
            ],
        );

        // Assert

        $response
            ->assertHasErrors()
            ->assertSee('SENT_REQUEST_NOT_FOUND');
    }

    public function test_it_integrates(): void
    {
        // Arrange

        $sentRequestStore = resolve(SentRequestStore::class);

        $sentRequestId = $sentRequestStore->put(
            sentRequest: [
                'route' => 'POST api/users',
                'request' => [
                    'method' => 'POST',
                    'path' => '/api/users',
                ],
                'response' => [
                    'status' => 422,
                    'headers' => [],
                    'body' => [
                        'message' => 'The email field is required.',
                        'errors' => [
                            'email' => ['The email field is required.'],
                        ],
                    ],
                ],
                'insight' => null,
            ],
        );

        // Act

        $response = $this->callTool(
            name: 'nimbus_explain_response',
            arguments: [
                'sent_request_id' => $sentRequestId,
            ],
        );

        // Assert

        $response
            ->assertOk()
            ->assertSee($sentRequestId)
            ->assertSee('email');
    }
}
