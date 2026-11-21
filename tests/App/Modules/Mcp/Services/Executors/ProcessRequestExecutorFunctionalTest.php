<?php

declare(strict_types=1);

namespace Sunchayn\Nimbus\Tests\App\Modules\Mcp\Services\Executors;

use Illuminate\Contracts\Process\ProcessResult;
use Illuminate\Process\PendingProcess;
use Illuminate\Support\Facades\Process;
use PHPUnit\Framework\Attributes\CoversClass;
use ReflectionProperty;
use Sunchayn\Nimbus\Modules\Mcp\DataTransferObjects\ExecutionRequestData;
use Sunchayn\Nimbus\Modules\Mcp\Exceptions\McpToolException;
use Sunchayn\Nimbus\Modules\Mcp\Services\Executors\ProcessRequestExecutor;
use Sunchayn\Nimbus\Tests\TestCase;

#[CoversClass(ProcessRequestExecutor::class)]
class ProcessRequestExecutorFunctionalTest extends TestCase
{
    public function test_it_reads_the_result_after_the_marker_and_ignores_other_output(): void
    {
        // Arrange

        $result = json_encode([
            'status' => 201,
            'headers' => ['x-a' => 'b'],
            'body' => '{}',
            'durationMs' => 1.5,
        ]);

        Process::fake([
            '*' => Process::result("noise\n".ProcessRequestExecutor::MARKER.$result."\n"),
        ]);

        $executionRequestData = new ExecutionRequestData(
            method: 'POST',
            path: '/api/users',
            transactionMode: true,
        );

        $executor = resolve(ProcessRequestExecutor::class);

        // Act

        $response = $executor->execute($executionRequestData);

        // Assert

        $this->assertSame(
            201,
            $response->status,
        );

        $this->assertSame(
            ['x-a' => 'b'],
            $response->headers,
        );
    }

    public function test_it_runs_the_request_command_with_the_request_as_input(): void
    {
        // Arrange

        $result = json_encode([
            'status' => 200,
            'headers' => [],
            'body' => '{}',
            'durationMs' => 1.0,
        ]);

        Process::fake([
            '*' => Process::result(ProcessRequestExecutor::MARKER.$result),
        ]);

        $executionRequestData = new ExecutionRequestData(
            method: 'POST',
            path: '/api/users',
            transactionMode: true,
        );

        $executor = resolve(ProcessRequestExecutor::class);

        // Act

        $executor->execute($executionRequestData);

        // Assert

        Process::assertRan(function (PendingProcess $process, ProcessResult $result): bool {
            return str_contains((string) json_encode($process->command), 'nimbus:mcp-request')
                && str_contains((string) $process->input, '"transactionMode":true');
        });
    }

    public function test_it_fails_when_the_child_prints_no_result(): void
    {
        // Arrange

        Process::fake([
            '*' => Process::result('boom', 'fatal', 255),
        ]);

        $executor = resolve(ProcessRequestExecutor::class);

        // Assert

        $this->expectException(McpToolException::class);

        // Act

        $executor->execute(new ExecutionRequestData(
            method: 'GET',
            path: '/',
        ));
    }

    public function test_it_starts_the_artisan_of_the_application_when_it_is_not_running_in_the_console(): void
    {
        // Arrange

        $isRunningInConsole = new ReflectionProperty($this->app, 'isRunningInConsole');

        $isRunningInConsole->setValue($this->app, false);

        $result = json_encode([
            'status' => 200,
            'headers' => [],
            'body' => '{}',
            'durationMs' => 1.0,
        ]);

        Process::fake([
            '*' => Process::result(ProcessRequestExecutor::MARKER.$result),
        ]);

        $executor = resolve(ProcessRequestExecutor::class);

        // Act

        $executor->execute(new ExecutionRequestData(
            method: 'GET',
            path: '/',
        ));

        // Assert

        Process::assertRan(function (PendingProcess $process): bool {
            $this->assertSame(
                [PHP_BINARY, 'artisan', 'nimbus:mcp-request'],
                $process->command,
            );

            $this->assertSame(
                base_path(),
                $process->path,
            );

            return true;
        });
    }
}
