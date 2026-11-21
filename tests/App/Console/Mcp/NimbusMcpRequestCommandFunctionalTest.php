<?php

declare(strict_types=1);

namespace Sunchayn\Nimbus\Tests\App\Console\Mcp;

use Illuminate\Support\Facades\Artisan;
use PHPUnit\Framework\Attributes\CoversClass;
use Sunchayn\Nimbus\Console\Mcp\NimbusMcpRequestCommand;
use Sunchayn\Nimbus\Modules\Mcp\Actions\HandleKernelRequestAction;
use Sunchayn\Nimbus\Modules\Mcp\DataTransferObjects\ExecutionRequestData;
use Sunchayn\Nimbus\Modules\Mcp\DataTransferObjects\ExecutionResultData;
use Sunchayn\Nimbus\Modules\Mcp\Services\Executors\ProcessRequestExecutor;
use Sunchayn\Nimbus\Tests\TestCase;

#[CoversClass(NimbusMcpRequestCommand::class)]
class NimbusMcpRequestCommandFunctionalTest extends TestCase
{
    public function test_it_handles_the_request_from_the_input_and_prints_the_marked_result(): void
    {
        // Arrange

        $handleKernelRequestActionMock = $this->mock(HandleKernelRequestAction::class);

        $this->registerCommandWithInput(json_encode([
            'method' => 'POST',
            'path' => '/api/users',
            'transactionMode' => true,
        ]));

        // Anticipate

        $handleKernelRequestActionMock
            ->shouldReceive('execute')
            ->andReturn(new ExecutionResultData(
                status: 201,
                headers: ['x-a' => 'b'],
                body: '{"id":1}',
                durationMs: 1.5,
            ));

        // Act

        $exitCode = Artisan::call(NimbusMcpRequestCommand::class);

        // Assert

        $this->assertSame(
            expected: 0,
            actual: $exitCode,
        );

        $this->assertStringContainsString(
            ProcessRequestExecutor::MARKER.'{"status":201',
            Artisan::output(),
        );

        $handleKernelRequestActionMock
            ->shouldHaveReceived('execute')
            ->once()
            ->withArgs(function (ExecutionRequestData $executionRequestData): bool {
                $this->assertSame('POST', $executionRequestData->method);
                $this->assertSame('/api/users', $executionRequestData->path);
                $this->assertTrue($executionRequestData->transactionMode);

                return true;
            });
    }

    public function test_it_fails_when_the_input_is_not_valid_json(): void
    {
        // Arrange

        $handleKernelRequestActionSpy = $this->spy(HandleKernelRequestAction::class);

        $this->registerCommandWithInput('not json');

        // Act

        $exitCode = Artisan::call(NimbusMcpRequestCommand::class);

        // Assert

        $this->assertSame(
            expected: 1,
            actual: $exitCode,
        );

        $handleKernelRequestActionSpy->shouldNotHaveReceived('execute');
    }

    /*
     * Mocks.
     */

    private function registerCommandWithInput(string $input): void
    {
        $command = new class($input) extends NimbusMcpRequestCommand
        {
            public function __construct(private readonly string $rawInput)
            {
                parent::__construct();
            }

            protected function readInput(): string
            {
                return $this->rawInput;
            }
        };

        Artisan::registerCommand($command);
    }
}
