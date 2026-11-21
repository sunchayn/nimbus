<?php

declare(strict_types=1);

namespace Sunchayn\Nimbus\Tests\Integration\Mcp;

use PHPUnit\Framework\Attributes\CoversClass;
use Sunchayn\Nimbus\Modules\Config\Exceptions\MisconfiguredValueException;
use Sunchayn\Nimbus\Modules\Mcp\Actions\DescribeRouteAction;
use Sunchayn\Nimbus\Modules\Mcp\Exceptions\McpToolException;
use Sunchayn\Nimbus\Modules\Mcp\Server\NimbusMcpServer;
use Sunchayn\Nimbus\Modules\Mcp\Server\Tools\NimbusTool;
use Sunchayn\Nimbus\Modules\Routes\Exceptions\RouteNotFoundException;
use Sunchayn\Nimbus\Modules\Workspace\Exceptions\WorkspaceException;
use Sunchayn\Nimbus\Tests\Concerns\InteractsWithMcpServer;
use Sunchayn\Nimbus\Tests\TestCase;

#[CoversClass(NimbusMcpServer::class)]
#[CoversClass(NimbusTool::class)]
class NimbusMcpServerTest extends TestCase
{
    use InteractsWithMcpServer;

    public function test_it_marks_the_read_tools_as_read_only(): void
    {
        // Act

        $tools = $this->listTools()->response['result']['tools'];

        // Assert

        $readOnlyHints = collect($tools)->mapWithKeys(
            fn (array $tool): array => [$tool['name'] => $tool['annotations']['readOnlyHint'] ?? false],
        );

        $this->assertSame(
            [
                'nimbus_list_routes' => true,
                'nimbus_describe_route' => true,
                'nimbus_build_payload' => true,
                'nimbus_validate_payload' => true,
                'nimbus_decrypt_value' => true,
                'nimbus_list_applications' => true,
                'nimbus_list_environments' => true,
                'nimbus_list_saved_requests' => true,
                'nimbus_send_request' => false,
                'nimbus_explain_response' => true,
                'nimbus_run_request' => false,
            ],
            $readOnlyHints->all(),
        );
    }

    public function test_it_serves_the_input_schema_of_a_tool(): void
    {
        // Act

        $tools = $this->listTools()->response['result']['tools'];

        // Assert

        $inputSchema = collect($tools)->firstWhere('name', 'nimbus_build_payload')['inputSchema'];

        $this->assertSame(
            [
                'type' => 'object',
                'properties' => [
                    'route' => [
                        'type' => 'string',
                        'description' => 'A route id from nimbus_list_routes, or a route name.',
                    ],
                    'mode' => [
                        'type' => 'string',
                        'enum' => ['valid', 'minimal', 'invalid'],
                        'description' => '`valid` fills every field, `minimal` fills required fields only, `invalid` breaks one rule.',
                    ],
                    'overrides' => [
                        'type' => 'object',
                        'description' => 'Values to set. A dotted key such as `user.name` reaches a nested field.',
                    ],
                    'application' => [
                        'type' => 'string',
                        'description' => 'An application key from nimbus_list_applications. Leave it out to search every application. Pass it when a route exists in more than one.',
                    ],
                ],
                'required' => ['route'],
            ],
            $inputSchema,
        );
    }

    public function test_it_rejects_arguments_that_break_the_input_schema(): void
    {
        // Act

        $response = $this->callTool(
            name: 'nimbus_describe_route',
            arguments: [],
        );

        // Assert

        $response
            ->assertHasErrors()
            ->assertSee('Invalid parameters for tool');
    }

    public function test_it_reports_a_missing_route_as_an_error(): void
    {
        // Arrange

        $describeRouteActionMock = $this->mock(DescribeRouteAction::class);

        $routeNotFoundException = RouteNotFoundException::becauseNoRouteMatches(reference: 'GET nowhere');

        // Anticipate

        $describeRouteActionMock
            ->shouldReceive('execute')
            ->andThrow($routeNotFoundException);

        // Act

        $response = $this->callTool(
            name: 'nimbus_describe_route',
            arguments: [
                'route' => 'GET nowhere',
            ],
        );

        // Assert

        $response->assertHasErrors();

        $this->assertSame(
            [
                'error' => [
                    'code' => 'ROUTE_NOT_FOUND',
                    'message' => 'No route matches `GET nowhere`.',
                    'hint' => 'Call nimbus_list_routes to find a route id.',
                ],
            ],
            $response->errorPayload(),
        );
    }

    public function test_it_reports_a_tool_failure_as_an_error(): void
    {
        // Arrange

        $describeRouteActionMock = $this->mock(DescribeRouteAction::class);

        $toolException = McpToolException::becauseRouteIsNotFound(
            reference: 'GET nowhere',
            suggestions: [],
        );

        // Anticipate

        $describeRouteActionMock
            ->shouldReceive('execute')
            ->andThrow($toolException);

        // Act

        $response = $this->callTool(
            name: 'nimbus_describe_route',
            arguments: [
                'route' => 'GET nowhere',
            ],
        );

        // Assert

        $response->assertHasErrors();

        $this->assertSame(
            'ROUTE_NOT_FOUND',
            $response->errorPayload()['error']['code'],
        );
    }

    public function test_it_reports_a_workspace_failure_as_an_error(): void
    {
        // Arrange

        $describeRouteActionMock = $this->mock(DescribeRouteAction::class);

        // Anticipate

        $describeRouteActionMock
            ->shouldReceive('execute')
            ->andThrow(WorkspaceException::disabled());

        // Act

        $response = $this->callTool(
            name: 'nimbus_describe_route',
            arguments: [
                'route' => 'GET nowhere',
            ],
        );

        // Assert

        $response->assertHasErrors();

        $this->assertSame(
            'INVALID_INPUT',
            $response->errorPayload()['error']['code'],
        );

        $this->assertSame(
            'Turn the workspace on in config/nimbus.php, then try again.',
            $response->errorPayload()['error']['hint'],
        );
    }

    public function test_it_reports_a_misconfigured_value_as_an_error(): void
    {
        // Arrange

        $describeRouteActionMock = $this->mock(DescribeRouteAction::class);

        $misconfiguredValueException = MisconfiguredValueException::becauseRouteProcessingStrategyIsInvalid('nope');

        // Anticipate

        $describeRouteActionMock
            ->shouldReceive('execute')
            ->andThrow($misconfiguredValueException);

        // Act

        $response = $this->callTool(
            name: 'nimbus_describe_route',
            arguments: [
                'route' => 'GET nowhere',
            ],
        );

        // Assert

        $response->assertHasErrors();

        $this->assertSame(
            'Fix the Nimbus config, then try again.',
            $response->errorPayload()['error']['hint'],
        );
    }
}
