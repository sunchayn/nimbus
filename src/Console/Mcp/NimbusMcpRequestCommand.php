<?php

declare(strict_types=1);

namespace Sunchayn\Nimbus\Console\Mcp;

use Illuminate\Console\Command;
use Sunchayn\Nimbus\Modules\Mcp\Actions\HandleKernelRequestAction;
use Sunchayn\Nimbus\Modules\Mcp\DataTransferObjects\ExecutionRequestData;
use Sunchayn\Nimbus\Modules\Mcp\Services\Executors\ProcessRequestExecutor;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Handles one request read from stdin and prints the result.
 * The MCP server starts it as a child process, so it is hidden from the command list.
 */
class NimbusMcpRequestCommand extends Command
{
    protected $signature = 'nimbus:mcp-request';

    protected $description = 'Handle one request for the Nimbus MCP server.';

    protected $hidden = true;

    public function handle(HandleKernelRequestAction $handleKernelRequestAction): int
    {
        $payload = json_decode($this->readInput(), true);

        if (! is_array($payload)) {
            $this->output->getErrorStyle()->error('The request payload is not valid JSON.');

            return self::FAILURE;
        }

        $executionResultData = $handleKernelRequestAction->execute(ExecutionRequestData::fromArray($payload));

        $this->printResult(result: $executionResultData->toArray());

        return self::SUCCESS;
    }

    protected function readInput(): string
    {
        return (string) file_get_contents('php://stdin');
    }

    /**
     * @param  array<string, mixed>  $result
     */
    private function printResult(array $result): void
    {
        $encodedResult = json_encode($result, JSON_UNESCAPED_SLASHES | JSON_INVALID_UTF8_SUBSTITUTE);

        // The leading new line keeps the marker on its own line, even after output from the application.
        $this->output->write(
            messages: PHP_EOL.ProcessRequestExecutor::MARKER.$encodedResult.PHP_EOL,
            newline: false,
            options: OutputInterface::OUTPUT_RAW,
        );
    }
}
