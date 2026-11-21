<?php

declare(strict_types=1);

namespace Sunchayn\Nimbus\Tests\Integration\Mcp;

use PHPUnit\Framework\Attributes\CoversClass;
use Sunchayn\Nimbus\Http\Api\Mcp\Controllers\McpController;
use Sunchayn\Nimbus\Http\Api\Mcp\PsrHttpBridge;
use Sunchayn\Nimbus\Modules\Mcp\Actions\ListEnvironmentsAction;
use Sunchayn\Nimbus\NimbusServiceProvider;
use Sunchayn\Nimbus\Tests\TestCase;

#[CoversClass(NimbusServiceProvider::class)]
#[CoversClass(McpController::class)]
#[CoversClass(PsrHttpBridge::class)]
class NimbusMcpHttpTest extends TestCase
{
    protected function defineEnvironment($app): void
    {
        parent::defineEnvironment($app);

        $app['config']->set('nimbus.mcp.http', true);
    }

    public function test_it_answers_a_local_request(): void
    {
        // Act

        $response = $this->postJson(
            route('nimbus.mcp'),
            ['jsonrpc' => '2.0', 'id' => 1, 'method' => 'tools/list'],
            ['Accept' => 'application/json'],
        );

        // Assert

        $response
            ->assertOk()
            ->assertSee('nimbus_list_routes');
    }

    public function test_it_answers_an_ipv6_loopback_request(): void
    {
        // Act

        $response = $this
            ->withServerVariables(['REMOTE_ADDR' => '::1'])
            ->postJson(
                route('nimbus.mcp'),
                ['jsonrpc' => '2.0', 'id' => 1, 'method' => 'tools/list'],
                ['Accept' => 'application/json'],
            );

        // Assert

        $response->assertOk();
    }

    public function test_it_serves_the_tools_as_json_without_a_session(): void
    {
        // Act

        $response = $this->postJson(
            route('nimbus.mcp'),
            ['jsonrpc' => '2.0', 'id' => 1, 'method' => 'tools/list'],
            ['Accept' => 'application/json'],
        );

        // Assert

        $response->assertHeader('Content-Type', 'application/json');

        $response->assertHeaderMissing('Mcp-Session-Id');

        $this->assertContains(
            'nimbus_describe_route',
            array_column($response->json('result.tools'), 'name'),
        );
    }

    public function test_it_calls_a_tool(): void
    {
        // Arrange

        $actionMock = $this->mock(ListEnvironmentsAction::class);

        // Anticipate

        $actionMock
            ->shouldReceive('execute')
            ->andReturn(['environments' => [['id' => 'current']]]);

        // Act

        $response = $this->postJson(
            route('nimbus.mcp'),
            [
                'jsonrpc' => '2.0',
                'id' => 1,
                'method' => 'tools/call',
                'params' => ['name' => 'nimbus_list_environments', 'arguments' => (object) []],
            ],
            ['Accept' => 'application/json'],
        );

        // Assert

        $response->assertJsonPath(
            'result.structuredContent.environments.0.id',
            'current',
        );
    }

    public function test_it_refuses_an_unknown_host(): void
    {
        // Act

        $path = route('nimbus.mcp', absolute: false);

        $response = $this->postJson(
            "http://evil.example{$path}",
            ['jsonrpc' => '2.0', 'id' => 1, 'method' => 'tools/list'],
        );

        // Assert

        $response->assertForbidden();
    }

    public function test_it_refuses_a_remote_request(): void
    {
        // Act

        $response = $this
            ->withServerVariables(['REMOTE_ADDR' => '203.0.113.9'])
            ->postJson(
                route('nimbus.mcp'),
                ['jsonrpc' => '2.0', 'id' => 1, 'method' => 'tools/list'],
            );

        // Assert

        $response->assertForbidden();
    }

    public function test_it_answers_a_request_to_the_host_of_the_application(): void
    {
        // Arrange

        config()->set('app.url', 'http://nimbus.test');

        $path = route('nimbus.mcp', absolute: false);

        // Act

        $response = $this->postJson(
            "http://nimbus.test{$path}",
            ['jsonrpc' => '2.0', 'id' => 1, 'method' => 'tools/list'],
            ['Accept' => 'application/json'],
        );

        // Assert

        $response->assertOk();
    }

    public function test_it_only_allows_loopback_hosts_when_the_application_url_has_no_host(): void
    {
        // Arrange

        config()->set('app.url', 'not a url');

        $path = route('nimbus.mcp', absolute: false);

        // Act

        $loopback = $this->postJson(
            "http://localhost{$path}",
            ['jsonrpc' => '2.0', 'id' => 1, 'method' => 'tools/list'],
            ['Accept' => 'application/json'],
        );

        $other = $this->postJson(
            "http://nimbus.test{$path}",
            ['jsonrpc' => '2.0', 'id' => 1, 'method' => 'tools/list'],
        );

        // Assert

        $loopback->assertOk();

        $other->assertForbidden();
    }
}
