<?php

declare(strict_types=1);

namespace Sunchayn\Nimbus\Tests\Integration\Mcp;

use PHPUnit\Framework\Attributes\CoversClass;
use Sunchayn\Nimbus\Modules\Mcp\Actions\ValidatePayloadAction;
use Sunchayn\Nimbus\Modules\Mcp\Exceptions\McpToolException;
use Sunchayn\Nimbus\Modules\Mcp\Server\Tools\ValidatePayloadTool;
use Sunchayn\Nimbus\Tests\App\Modules\Mcp\Concerns\FakesExtractedRoutes;
use Sunchayn\Nimbus\Tests\Concerns\InteractsWithMcpServer;
use Sunchayn\Nimbus\Tests\TestCase;

#[CoversClass(ValidatePayloadTool::class)]
class NimbusMcpValidatePayloadToolTest extends TestCase
{
    use FakesExtractedRoutes;
    use InteractsWithMcpServer;

    public function test_it_passes_the_route_and_the_body_to_the_action(): void
    {
        // Arrange

        $validatePayloadActionMock = $this->mock(ValidatePayloadAction::class);

        // Anticipate

        $validatePayloadActionMock
            ->shouldReceive('execute')
            ->andReturn(
                [
                    'route' => 'POST api/users',
                    'valid' => true,
                    'errors' => [],
                ],
            );

        // Act

        $this->callTool(
            name: 'nimbus_validate_payload',
            arguments: [
                'route' => 'POST api/users',
                'body' => [
                    'name' => 'Ada',
                ],
            ],
        );

        // Assert

        $validatePayloadActionMock
            ->shouldHaveReceived('execute')
            ->once()
            ->withArgs(function (string $route, array $body): bool {
                $this->assertSame(
                    'POST api/users',
                    $route,
                );

                $this->assertSame(
                    ['name' => 'Ada'],
                    $body,
                );

                return true;
            });
    }

    public function test_it_returns_the_validation_result(): void
    {
        // Arrange

        $validatePayloadActionMock = $this->mock(ValidatePayloadAction::class);

        // Anticipate

        $validatePayloadActionMock
            ->shouldReceive('execute')
            ->andReturn(
                [
                    'route' => 'POST api/users',
                    'valid' => false,
                    'errors' => [
                        [
                            'path' => 'email',
                            'rule' => 'required',
                            'message' => 'The email is required.',
                        ],
                    ],
                ],
            );

        // Act

        $response = $this->callTool(
            name: 'nimbus_validate_payload',
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
            ->assertSee('The email is required.');
    }

    public function test_it_reports_a_failure_as_an_error(): void
    {
        // Arrange

        $validatePayloadActionMock = $this->mock(ValidatePayloadAction::class);

        $routeNotFoundException = McpToolException::becauseRouteIsNotFound(
            reference: 'GET nowhere',
            suggestions: [],
        );

        // Anticipate

        $validatePayloadActionMock
            ->shouldReceive('execute')
            ->andThrow($routeNotFoundException);

        // Act

        $response = $this->callTool(
            name: 'nimbus_validate_payload',
            arguments: [
                'route' => 'GET nowhere',
                'body' => [
                    'name' => 'Ada',
                ],
            ],
        );

        // Assert

        $response
            ->assertHasErrors()
            ->assertSee('ROUTE_NOT_FOUND');
    }

    public function test_it_integrates(): void
    {
        // Arrange

        $this->fakeExtractedRoutes();

        // Act

        $response = $this->callTool(
            name: 'nimbus_validate_payload',
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
            ->assertSee('email');

        $this->assertFalse($response->structuredContent()['valid']);
    }
}
