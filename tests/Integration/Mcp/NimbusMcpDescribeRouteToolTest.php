<?php

declare(strict_types=1);

namespace Sunchayn\Nimbus\Tests\Integration\Mcp;

use PHPUnit\Framework\Attributes\CoversClass;
use Sunchayn\Nimbus\Modules\Mcp\Actions\DescribeRouteAction;
use Sunchayn\Nimbus\Modules\Mcp\Exceptions\McpToolException;
use Sunchayn\Nimbus\Modules\Mcp\Server\Tools\DescribeRouteTool;
use Sunchayn\Nimbus\Tests\App\Modules\Mcp\Concerns\FakesExtractedRoutes;
use Sunchayn\Nimbus\Tests\Concerns\InteractsWithMcpServer;
use Sunchayn\Nimbus\Tests\TestCase;

#[CoversClass(DescribeRouteTool::class)]
class NimbusMcpDescribeRouteToolTest extends TestCase
{
    use FakesExtractedRoutes;
    use InteractsWithMcpServer;

    public function test_it_passes_the_route_to_the_describe_route_action(): void
    {
        // Arrange

        $describeRouteActionMock = $this->mock(DescribeRouteAction::class);

        // Anticipate

        $describeRouteActionMock
            ->shouldReceive('execute')
            ->andReturn(
                [
                    'id' => 'POST api/users',
                ],
            );

        // Act

        $this->callTool(
            name: 'nimbus_describe_route',
            arguments: [
                'route' => 'users.store',
            ],
        );

        // Assert

        $describeRouteActionMock
            ->shouldHaveReceived('execute')
            ->once()
            ->with('users.store', null);
    }

    public function test_it_reports_a_missing_route_as_an_error(): void
    {
        // Arrange

        $describeRouteActionMock = $this->mock(DescribeRouteAction::class);

        $routeNotFoundException = McpToolException::becauseRouteIsNotFound(
            reference: 'GET nowhere',
            suggestions: [],
        );

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

    public function test_it_integrates(): void
    {
        // Arrange

        $this->fakeExtractedRoutes();

        // Act

        $response = $this->callTool(
            name: 'nimbus_describe_route',
            arguments: [
                'route' => 'users.store',
            ],
        );

        // Assert

        $response
            ->assertOk()
            ->assertSee('POST api/users')
            ->assertSee('email');
    }
}
