<?php

declare(strict_types=1);

namespace Sunchayn\Nimbus\Tests\App\Modules\Magic\Toolbox\Actions;

use Illuminate\Cookie\CookieValuePrefix;
use Illuminate\Support\Facades\Crypt;
use PHPUnit\Framework\Attributes\CoversClass;
use Sunchayn\Nimbus\Modules\Magic\Toolbox\Actions\EncryptValueAction;
use Sunchayn\Nimbus\Tests\TestCase;

#[CoversClass(EncryptValueAction::class)]
class EncryptValueActionFunctionalTest extends TestCase
{
    public function test_it_encrypts_a_value(): void
    {
        // Arrange

        $action = resolve(EncryptValueAction::class);

        // Act

        $encrypted = $action->execute('dark');

        // Assert

        $this->assertSame('dark', Crypt::decryptString($encrypted));
    }

    public function test_it_adds_the_cookie_prefix_when_a_cookie_name_is_given(): void
    {
        // Arrange

        $action = resolve(EncryptValueAction::class);

        // Act

        $encrypted = $action->execute('dark', 'theme');

        // Assert

        $plain = Crypt::decryptString($encrypted);

        $this->assertSame('dark', CookieValuePrefix::remove($plain));
        $this->assertStringStartsWith(CookieValuePrefix::create('theme', Crypt::getKey()), $plain);
    }

    public function test_it_ignores_an_empty_cookie_name(): void
    {
        // Arrange

        $action = resolve(EncryptValueAction::class);

        // Act

        $encrypted = $action->execute('dark', '');

        // Assert

        $this->assertSame('dark', Crypt::decryptString($encrypted));
    }
}
