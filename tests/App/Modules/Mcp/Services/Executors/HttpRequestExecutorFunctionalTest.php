<?php

declare(strict_types=1);

namespace Sunchayn\Nimbus\Tests\App\Modules\Mcp\Services\Executors;

use PHPUnit\Framework\Attributes\CoversClass;
use Sunchayn\Nimbus\Modules\Magic\DataTransferObjects\InsightData;
use Sunchayn\Nimbus\Modules\Mcp\DataTransferObjects\ExecutionRequestData;
use Sunchayn\Nimbus\Modules\Mcp\Exceptions\McpToolException;
use Sunchayn\Nimbus\Modules\Mcp\Services\Executors\HttpRequestExecutor;
use Sunchayn\Nimbus\Modules\Relay\Actions\RequestRelayAction;
use Sunchayn\Nimbus\Modules\Relay\DataTransferObjects\PrintableResponseBodyData;
use Sunchayn\Nimbus\Modules\Relay\DataTransferObjects\RelayedRequestResponseData;
use Sunchayn\Nimbus\Modules\Relay\DataTransferObjects\RequestRelayData;
use Sunchayn\Nimbus\Modules\Relay\Services\Authorization\Enums\AuthorizationTypeEnum;
use Sunchayn\Nimbus\Tests\TestCase;

#[CoversClass(HttpRequestExecutor::class)]
class HttpRequestExecutorFunctionalTest extends TestCase
{
    public function test_it_relays_the_request_to_the_current_host(): void
    {
        // Arrange

        config(['nimbus.base_url' => 'https://app.test/']);

        $requestRelayActionMock = $this->mock(RequestRelayAction::class);

        $executor = resolve(HttpRequestExecutor::class);

        // Anticipate

        $requestRelayActionMock
            ->shouldReceive('execute')
            ->andReturn($this->makeRelayedResponse());

        // Act

        $executor->execute(new ExecutionRequestData(
            method: 'POST',
            path: '/api/users',
            query: ['page' => '2'],
            headers: ['X-Custom' => 'yes'],
            body: ['name' => 'Jane'],
            transactionMode: true,
        ));

        // Assert

        $requestRelayActionMock
            ->shouldHaveReceived('execute')
            ->once()
            ->withArgs(function (RequestRelayData $requestRelayData): bool {
                $this->assertSame('post', $requestRelayData->method);
                $this->assertSame('https://app.test/api/users', $requestRelayData->endpoint);
                $this->assertSame(AuthorizationTypeEnum::None, $requestRelayData->authorization->type);
                $this->assertSame(['x-custom' => 'yes', 'accept' => 'application/json'], $requestRelayData->headers);
                $this->assertSame(['name' => 'Jane'], $requestRelayData->body);
                $this->assertSame(['page' => '2'], $requestRelayData->queryParameters);
                $this->assertTrue($requestRelayData->transactionMode);
                $this->assertTrue($requestRelayData->isCurrentHost);

                return true;
            });
    }

    public function test_it_builds_the_result_from_the_relayed_response(): void
    {
        // Arrange

        $requestRelayActionMock = $this->mock(RequestRelayAction::class);

        $executor = resolve(HttpRequestExecutor::class);

        // Anticipate

        $requestRelayActionMock
            ->shouldReceive('execute')
            ->andReturn($this->makeRelayedResponse());

        // Act

        $result = $executor->execute(new ExecutionRequestData(
            method: 'GET',
            path: '/api/users',
        ));

        // Assert

        $this->assertSame(
            201,
            $result->status,
        );

        $this->assertSame(
            ['content-type' => 'application/json, charset=utf-8', 'x-a' => 'b'],
            $result->headers,
        );

        $this->assertSame(
            "{\n    \"id\": 1\n}",
            $result->body,
        );

        $this->assertSame(
            12.5,
            $result->durationMs,
        );

        $this->assertNull($result->insight);
    }

    public function test_it_targets_an_enabled_environment(): void
    {
        // Arrange

        config([
            'nimbus.environments' => [
                'staging' => [
                    'name' => 'Staging',
                    'base_url' => 'https://staging.test',
                    'enabled' => true,
                ],
            ],
        ]);

        $requestRelayActionMock = $this->mock(RequestRelayAction::class);

        $executor = resolve(HttpRequestExecutor::class);

        // Anticipate

        $requestRelayActionMock
            ->shouldReceive('execute')
            ->andReturn($this->makeRelayedResponse());

        // Act

        $executor->execute(new ExecutionRequestData(
            method: 'GET',
            path: 'api/users',
            environment: 'staging',
        ));

        // Assert

        $requestRelayActionMock
            ->shouldHaveReceived('execute')
            ->once()
            ->withArgs(function (RequestRelayData $requestRelayData): bool {
                $this->assertSame('https://staging.test/api/users', $requestRelayData->endpoint);
                $this->assertSame('staging', $requestRelayData->environment);
                $this->assertFalse($requestRelayData->isCurrentHost);

                return true;
            });
    }

    public function test_it_refuses_a_disabled_environment(): void
    {
        // Arrange

        config([
            'nimbus.environments' => [
                'staging' => [
                    'name' => 'Staging',
                    'base_url' => 'https://staging.test',
                    'enabled' => false,
                ],
            ],
        ]);

        $requestRelayActionSpy = $this->spy(RequestRelayAction::class);

        $executor = resolve(HttpRequestExecutor::class);

        // Assert

        $this->expectException(McpToolException::class);

        // Act

        try {
            $executor->execute(new ExecutionRequestData(
                method: 'GET',
                path: '/api/users',
                environment: 'staging',
            ));
        } finally {
            $requestRelayActionSpy->shouldNotHaveReceived('execute');
        }
    }

    public function test_it_wraps_a_relay_failure(): void
    {
        // Arrange

        $requestRelayActionMock = $this->mock(RequestRelayAction::class);

        $executor = resolve(HttpRequestExecutor::class);

        // Anticipate

        $requestRelayActionMock
            ->shouldReceive('execute')
            ->andThrow(new \RuntimeException('Connection refused'));

        // Assert

        $this->expectException(McpToolException::class);

        $this->expectExceptionMessage('Connection refused');

        // Act

        $executor->execute(new ExecutionRequestData(
            method: 'GET',
            path: '/api/users',
        ));
    }

    /*
     * Generators.
     */

    private function makeRelayedResponse(?InsightData $insightData = null): RelayedRequestResponseData
    {
        return new RelayedRequestResponseData(
            statusCode: 201,
            statusText: 'Created',
            body: new PrintableResponseBodyData(['id' => 1]),
            headers: [
                'Content-Type' => ['application/json', 'charset=utf-8'],
                'X-A' => ['b'],
            ],
            durationMs: 12.5,
            timestamp: 1_700_000_000,
            cookies: [],
            insight: $insightData,
        );
    }
}
