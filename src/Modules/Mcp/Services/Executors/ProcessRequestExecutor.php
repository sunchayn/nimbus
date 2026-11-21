<?php

declare(strict_types=1);

namespace Sunchayn\Nimbus\Modules\Mcp\Services\Executors;

use Illuminate\Support\Facades\Process;
use Illuminate\Support\Str;
use Sunchayn\Nimbus\Modules\Mcp\Contracts\RequestExecutor;
use Sunchayn\Nimbus\Modules\Mcp\DataTransferObjects\ExecutionRequestData;
use Sunchayn\Nimbus\Modules\Mcp\DataTransferObjects\ExecutionResultData;
use Sunchayn\Nimbus\Modules\Mcp\Exceptions\McpToolException;

/**
 * Handles the request in a fresh PHP process that boots the application.
 * Nothing stays in memory between two requests, at the cost of one boot for each.
 * The child process is the `nimbus:mcp-request` command.
 */
class ProcessRequestExecutor implements RequestExecutor
{
    public const MARKER = 'NIMBUS-MCP-RESULT ';

    public const TIMEOUT_SECONDS = 120;

    public function execute(ExecutionRequestData $executionRequestData): ExecutionResultData
    {
        [$workingDirectory, $script] = $this->resolveEntryPoint();

        $processResult = Process::path($workingDirectory)
            ->timeout(self::TIMEOUT_SECONDS)
            ->input((string) json_encode($executionRequestData->toArray()))
            ->run([PHP_BINARY, $script, 'nimbus:mcp-request']);

        $result = $this->parseResult(output: $processResult->output());

        if ($result === null) {
            $reason = trim($processResult->errorOutput()) ?: trim($processResult->output());

            throw McpToolException::becauseRequestFailed(reason: 'The request process returned no result. '.Str::substr($reason, 0, 500));
        }

        return ExecutionResultData::fromArray($result);
    }

    /**
     * The child boots the application the same way the current process did.
     * This matters for hosts such as Testbench, where the base path has no artisan or vendor folder.
     *
     * @return array{0: string, 1: string}
     */
    private function resolveEntryPoint(): array
    {
        $script = $_SERVER['argv'][0] ?? null;

        if (app()->runningInConsole() && is_string($script) && is_file($script)) {
            return [getcwd() ?: base_path(), $script];
        }

        return [base_path(), 'artisan'];
    }

    /**
     * The application may print on its own, so the result is the last line with the marker.
     *
     * @return array<string, mixed>|null
     */
    private function parseResult(string $output): ?array
    {
        $resultLine = collect(explode("\n", $output))
            ->reverse()
            ->first(static fn (string $line): bool => str_starts_with($line, self::MARKER));

        if ($resultLine === null) {
            return null;
        }

        $result = json_decode(substr($resultLine, strlen(self::MARKER)), true);

        return is_array($result) ? $result : null;
    }
}
