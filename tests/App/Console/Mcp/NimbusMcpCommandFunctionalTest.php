<?php

declare(strict_types=1);

namespace Sunchayn\Nimbus\Tests\App\Console\Mcp;

use Mcp\Server\Transport\StdioTransport;
use PHPUnit\Framework\Attributes\CoversClass;
use Sunchayn\Nimbus\Console\Mcp\NimbusMcpCommand;
use Sunchayn\Nimbus\Tests\Concerns\UsesTemporaryBasePath;
use Sunchayn\Nimbus\Tests\Support\McpStdioTransport;
use Sunchayn\Nimbus\Tests\TestCase;

#[CoversClass(NimbusMcpCommand::class)]
class NimbusMcpCommandFunctionalTest extends TestCase
{
    use UsesTemporaryBasePath;

    protected function setUp(): void
    {
        parent::setUp();

        $this->useTemporaryBasePath();
    }

    public function test_it_prints_the_client_config(): void
    {
        // Act & Assert

        $this->artisan(
            command: NimbusMcpCommand::class,
            parameters: ['--install' => true],
        )
            ->expectsOutputToContain('nimbus:mcp')
            ->assertSuccessful();
    }

    public function test_it_merges_the_config_into_the_mcp_file(): void
    {
        // Arrange

        $path = base_path('.mcp.json');

        $existingConfig = (string) json_encode(['mcpServers' => ['other' => ['command' => 'x']]]);

        file_put_contents(
            $path,
            $existingConfig,
        );

        // Act

        $this->artisan(
            command: NimbusMcpCommand::class,
            parameters: ['--install' => true, '--write' => true],
        )
            ->assertSuccessful();

        // Assert

        $written = json_decode((string) file_get_contents($path), true);

        $this->assertArrayHasKey(
            'other',
            $written['mcpServers'],
        );

        $this->assertArrayHasKey(
            'nimbus',
            $written['mcpServers'],
        );
    }

    public function test_it_creates_the_mcp_file_when_it_is_missing(): void
    {
        // Act

        $this->artisan(
            command: NimbusMcpCommand::class,
            parameters: ['--install' => true, '--write' => true],
        )
            ->assertSuccessful();

        // Assert

        $written = json_decode((string) file_get_contents(base_path('.mcp.json')), true);

        $this->assertArrayHasKey(
            'nimbus',
            $written['mcpServers'],
        );
    }

    public function test_it_refuses_to_merge_into_an_invalid_mcp_file(): void
    {
        // Arrange

        $path = base_path('.mcp.json');

        file_put_contents($path, 'not json');

        // Act & Assert

        $this->artisan(
            command: NimbusMcpCommand::class,
            parameters: ['--install' => true, '--write' => true],
        )
            ->assertFailed();

        $this->assertSame(
            'not json',
            file_get_contents($path),
        );
    }

    public function test_it_rejects_an_unknown_level(): void
    {
        // Arrange

        config(['nimbus.mcp.level' => 'root']);

        // Act & Assert

        $this->artisan(NimbusMcpCommand::class)
            ->expectsOutputToContain('The `nimbus.mcp.level` config must be `read`, `send`, or `write`.')
            ->assertFailed();
    }

    public function test_it_answers_a_client_on_the_stdio_transport(): void
    {
        // Arrange

        $input = fopen('php://temp', 'w+');
        $output = fopen('php://temp', 'w+');

        fwrite($input, json_encode($this->initializeMessage())."\n");

        rewind($input);

        $this->app->bind(
            StdioTransport::class,
            fn (): McpStdioTransport => new McpStdioTransport($input, $output),
        );

        // Act

        $exitCode = $this->artisan(NimbusMcpCommand::class)->run();

        $this->assertSame(
            0,
            $exitCode,
        );

        // Assert

        rewind($output);

        $line = (string) fgets($output);

        $response = json_decode($line, true);

        $this->assertSame(
            'Nimbus',
            $response['result']['serverInfo']['name'] ?? null,
            "The server did not answer the initialize request. It wrote: {$line}",
        );
    }

    public function test_it_keeps_stray_output_off_the_protocol_while_the_server_runs(): void
    {
        // Arrange

        $displayErrorsBefore = ini_get('display_errors');

        $bufferLevelBefore = ob_get_level();

        $recordingTransport = new class(fopen('php://temp', 'w+'), fopen('php://temp', 'w+')) extends McpStdioTransport
        {
            public string|false $displayErrors = false;

            public int $bufferLevel = 0;

            public function close(): void
            {
                $this->displayErrors = ini_get('display_errors');

                $this->bufferLevel = ob_get_level();
            }
        };

        $this->app->bind(
            StdioTransport::class,
            fn (): McpStdioTransport => $recordingTransport,
        );

        // Act

        $exitCode = $this->artisan(NimbusMcpCommand::class)->run();

        $this->assertSame(
            0,
            $exitCode,
        );

        // Assert

        $this->assertSame(
            'stderr',
            $recordingTransport->displayErrors,
        );

        $this->assertSame(
            $bufferLevelBefore + 1,
            $recordingTransport->bufferLevel,
        );

        $this->assertSame(
            $displayErrorsBefore,
            ini_get('display_errors'),
        );

        $this->assertSame(
            $bufferLevelBefore,
            ob_get_level(),
        );
    }

    public function test_it_answers_a_real_client_over_a_process(): void
    {
        // Arrange

        $process = proc_open(
            command: [PHP_BINARY, dirname(__DIR__, 4).'/vendor/bin/testbench', 'nimbus:mcp'],
            descriptor_spec: [
                0 => ['pipe', 'r'],
                1 => ['pipe', 'w'],
                2 => ['pipe', 'w'],
            ],
            pipes: $pipes,
            cwd: dirname(__DIR__, 4),
        );

        $this->assertIsResource($process);

        [$stdin, $stdout, $stderr] = $pipes;

        stream_set_blocking($stdout, false);

        // Act

        $this->writeMessage($stdin, $this->initializeMessage());

        $this->writeMessage($stdin, ['jsonrpc' => '2.0', 'method' => 'notifications/initialized']);

        $this->writeMessage($stdin, ['jsonrpc' => '2.0', 'id' => 2, 'method' => 'tools/list']);

        $lines = $this->readLinesUntil(
            stream: $stdout,
            expectedLines: 2,
            timeoutInSeconds: 30,
        );

        fclose($stdin);

        $exitCode = $this->waitForExit(
            process: $process,
            timeoutInSeconds: 30,
        );

        $remainingOutput = stream_get_contents($stdout);

        $errors = stream_get_contents($stderr);

        proc_close($process);

        // Assert

        $this->assertCount(
            2,
            $lines,
            "The server did not answer in time.\n{$errors}",
        );

        $responses = array_map(
            fn (string $line): array => json_decode($line, true, flags: JSON_THROW_ON_ERROR),
            $lines,
        );

        $this->assertSame(
            'Nimbus',
            $responses[0]['result']['serverInfo']['name'],
        );

        $this->assertContains(
            'nimbus_list_routes',
            array_column($responses[1]['result']['tools'], 'name'),
        );

        $this->assertSame(
            '',
            $remainingOutput,
        );

        $this->assertSame(
            0,
            $exitCode,
        );
    }

    /*
     * Generators.
     */

    /**
     * @return array<string, mixed>
     */
    private function initializeMessage(): array
    {
        return [
            'jsonrpc' => '2.0',
            'id' => 1,
            'method' => 'initialize',
            'params' => [
                'protocolVersion' => '2025-06-18',
                'capabilities' => (object) [],
                'clientInfo' => ['name' => 'phpunit', 'version' => '1.0.0'],
            ],
        ];
    }

    /*
     * Helpers.
     */

    /**
     * @param  resource  $stream
     * @param  array<string, mixed>  $message
     */
    private function writeMessage($stream, array $message): void
    {
        fwrite($stream, json_encode($message)."\n");
    }

    /**
     * Reads complete lines from a non blocking stream, and stops at the deadline so a hang fails the test.
     *
     * @param  resource  $stream
     * @return list<string>
     */
    private function readLinesUntil($stream, int $expectedLines, int $timeoutInSeconds): array
    {
        $deadline = microtime(true) + $timeoutInSeconds;

        $buffer = '';

        while (microtime(true) < $deadline && substr_count($buffer, "\n") < $expectedLines) {
            $read = [$stream];
            $write = $except = null;

            if (stream_select($read, $write, $except, 0, 200000) > 0) {
                $buffer .= (string) fread($stream, 65536);
            }
        }

        return array_values(array_filter(explode("\n", $buffer)));
    }

    /**
     * @param  resource  $process
     */
    private function waitForExit($process, int $timeoutInSeconds): int
    {
        $deadline = microtime(true) + $timeoutInSeconds;

        while (microtime(true) < $deadline) {
            $status = proc_get_status($process);

            if (! $status['running']) {
                return $status['exitcode'];
            }

            usleep(50000);
        }

        proc_terminate($process);

        $this->fail('The server did not stop after its input was closed.');
    }
}
