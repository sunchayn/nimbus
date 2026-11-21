<?php

declare(strict_types=1);

namespace Sunchayn\Nimbus\Tests\App\Modules\Mcp\Actions;

use Illuminate\Contracts\Encryption\Encrypter;
use Illuminate\Cookie\CookieValuePrefix;
use PHPUnit\Framework\Attributes\CoversClass;
use Sunchayn\Nimbus\Modules\Mcp\Actions\DecryptValueAction;
use Sunchayn\Nimbus\Modules\Mcp\Exceptions\McpToolException;
use Sunchayn\Nimbus\Tests\TestCase;

#[CoversClass(DecryptValueAction::class)]
class DecryptValueActionFunctionalTest extends TestCase
{
    public function test_it_decrypts_a_cookie(): void
    {
        // Arrange

        $encrypter = resolve(Encrypter::class);

        $prefix = CookieValuePrefix::create(
            'remember',
            $encrypter->getKey(),
        );

        $cookie = $encrypter->encrypt(
            $prefix.'42|token',
            false,
        );

        $action = resolve(DecryptValueAction::class);

        // Act

        $result = $action->execute(
            value: $cookie,
            kind: 'cookie',
            name: 'remember',
        );

        // Assert

        $this->assertSame(
            '42|token',
            $result['value'],
        );
    }

    public function test_it_decrypts_a_payload(): void
    {
        // Arrange

        $encrypter = resolve(Encrypter::class);

        $action = resolve(DecryptValueAction::class);

        // Act

        $result = $action->execute(
            value: $encrypter->encrypt(['id' => 7]),
            kind: 'payload',
        );

        // Assert

        $this->assertSame(
            ['id' => 7],
            $result['value'],
        );
    }

    public function test_it_fails_on_garbage(): void
    {
        // Arrange

        $action = resolve(DecryptValueAction::class);

        // Assert

        $this->expectException(McpToolException::class);

        // Act

        $action->execute(value: 'garbage');
    }

    public function test_it_rejects_an_unknown_kind(): void
    {
        // Arrange

        $encrypter = resolve(Encrypter::class);

        $action = resolve(DecryptValueAction::class);

        // Act

        try {
            $action->execute(
                value: $encrypter->encrypt('x', false),
                kind: 'session',
            );

            $this->fail('The action must fail.');
        } catch (McpToolException $mcpToolException) {
            // Assert

            $this->assertSame(
                McpToolException::INVALID_INPUT,
                $mcpToolException->errorCode,
            );

            $this->assertStringContainsString(
                'Unknown kind `session`.',
                $mcpToolException->getMessage(),
            );
        }
    }

    public function test_it_returns_the_whole_cookie_value_when_no_name_is_given(): void
    {
        // Arrange

        $encrypter = resolve(Encrypter::class);

        $action = resolve(DecryptValueAction::class);

        // Act

        $result = $action->execute(
            value: $encrypter->encrypt('prefix|value', false),
            kind: 'cookie',
        );

        // Assert

        $this->assertSame(
            'prefix|value',
            $result['value'],
        );
    }

    public function test_it_returns_the_whole_cookie_value_when_it_has_no_prefix(): void
    {
        // Arrange

        $encrypter = resolve(Encrypter::class);

        $action = resolve(DecryptValueAction::class);

        // Act

        $result = $action->execute(
            value: $encrypter->encrypt('plain', false),
            kind: 'cookie',
            name: 'remember',
        );

        // Assert

        $this->assertSame(
            'plain',
            $result['value'],
        );
    }

    public function test_it_fails_when_the_decrypted_value_is_not_a_string(): void
    {
        // Arrange

        $encrypterMock = $this->mock(Encrypter::class);

        $encrypterMock
            ->shouldReceive('decrypt')
            ->andReturn(['not', 'a', 'string']);

        $action = resolve(DecryptValueAction::class);

        // Assert

        $this->expectException(McpToolException::class);

        // Act

        $action->execute(value: 'anything');
    }
}
