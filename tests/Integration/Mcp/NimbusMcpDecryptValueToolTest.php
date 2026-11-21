<?php

declare(strict_types=1);

namespace Sunchayn\Nimbus\Tests\Integration\Mcp;

use Illuminate\Support\Facades\Crypt;
use PHPUnit\Framework\Attributes\CoversClass;
use Sunchayn\Nimbus\Modules\Mcp\Actions\DecryptValueAction;
use Sunchayn\Nimbus\Modules\Mcp\Exceptions\McpToolException;
use Sunchayn\Nimbus\Modules\Mcp\Server\Tools\DecryptValueTool;
use Sunchayn\Nimbus\Tests\Concerns\InteractsWithMcpServer;
use Sunchayn\Nimbus\Tests\TestCase;

#[CoversClass(DecryptValueTool::class)]
class NimbusMcpDecryptValueToolTest extends TestCase
{
    use InteractsWithMcpServer;

    public function test_it_passes_the_value_the_kind_and_the_name_to_the_action(): void
    {
        // Arrange

        $decryptValueActionMock = $this->mock(DecryptValueAction::class);

        // Anticipate

        $decryptValueActionMock
            ->shouldReceive('execute')
            ->andReturn(
                [
                    'kind' => 'cookie',
                    'value' => '42|token',
                ],
            );

        // Act

        $this->callTool(
            name: 'nimbus_decrypt_value',
            arguments: [
                'value' => 'encrypted',
                'kind' => 'cookie',
                'name' => 'remember',
            ],
        );

        // Assert

        $decryptValueActionMock
            ->shouldHaveReceived('execute')
            ->once()
            ->withArgs(function (string $value, string $kind, ?string $name): bool {
                $this->assertSame(
                    'encrypted',
                    $value,
                );

                $this->assertSame(
                    'cookie',
                    $kind,
                );

                $this->assertSame(
                    'remember',
                    $name,
                );

                return true;
            });
    }

    public function test_it_defaults_to_the_cookie_kind(): void
    {
        // Arrange

        $decryptValueActionMock = $this->mock(DecryptValueAction::class);

        // Anticipate

        $decryptValueActionMock
            ->shouldReceive('execute')
            ->andReturn(
                [
                    'kind' => 'cookie',
                    'value' => 'x',
                ],
            );

        // Act

        $this->callTool(
            name: 'nimbus_decrypt_value',
            arguments: [
                'value' => 'encrypted',
            ],
        );

        // Assert

        $decryptValueActionMock
            ->shouldHaveReceived('execute')
            ->once()
            ->withArgs(function (string $value, string $kind, ?string $name): bool {
                $this->assertSame(
                    'cookie',
                    $kind,
                );

                $this->assertNull($name);

                return true;
            });
    }

    public function test_it_returns_the_decrypted_value(): void
    {
        // Arrange

        $decryptValueActionMock = $this->mock(DecryptValueAction::class);

        // Anticipate

        $decryptValueActionMock
            ->shouldReceive('execute')
            ->andReturn(
                [
                    'kind' => 'payload',
                    'value' => 'decrypted-secret',
                ],
            );

        // Act

        $response = $this->callTool(
            name: 'nimbus_decrypt_value',
            arguments: [
                'value' => 'encrypted',
                'kind' => 'payload',
            ],
        );

        // Assert

        $response
            ->assertOk()
            ->assertSee('decrypted-secret');
    }

    public function test_it_reports_a_failure_as_an_error(): void
    {
        // Arrange

        $decryptValueActionMock = $this->mock(DecryptValueAction::class);

        // Anticipate

        $decryptValueActionMock
            ->shouldReceive('execute')
            ->andThrow(McpToolException::becauseDecryptionFailed());

        // Act

        $response = $this->callTool(
            name: 'nimbus_decrypt_value',
            arguments: [
                'value' => 'garbage',
            ],
        );

        // Assert

        $response
            ->assertHasErrors()
            ->assertSee('DECRYPTION_FAILED');
    }

    public function test_it_integrates(): void
    {
        // Arrange

        $encryptedValue = Crypt::encrypt(
            value: 'decrypted-secret',
            serialize: false,
        );

        // Act

        $response = $this->callTool(
            name: 'nimbus_decrypt_value',
            arguments: [
                'value' => $encryptedValue,
                'kind' => 'payload',
            ],
        );

        // Assert

        $response
            ->assertOk()
            ->assertSee('decrypted-secret');
    }
}
