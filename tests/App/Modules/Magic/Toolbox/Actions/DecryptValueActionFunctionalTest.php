<?php

declare(strict_types=1);

namespace Sunchayn\Nimbus\Tests\App\Modules\Magic\Toolbox\Actions;

use Illuminate\Cookie\CookieValuePrefix;
use Illuminate\Support\Facades\Crypt;
use PHPUnit\Framework\Attributes\CoversClass;
use Sunchayn\Nimbus\Modules\Magic\Toolbox\Actions\DecryptValueAction;
use Sunchayn\Nimbus\Modules\Magic\Toolbox\Exceptions\InvalidToolInputException;
use Sunchayn\Nimbus\Tests\TestCase;

#[CoversClass(DecryptValueAction::class)]
class DecryptValueActionFunctionalTest extends TestCase
{
    public function test_it_decrypts_a_value(): void
    {
        // Arrange

        $action = resolve(DecryptValueAction::class);

        // Act

        $result = $action->execute(Crypt::encryptString('secret'));

        // Assert

        $this->assertSame(['value' => 'secret', 'prefix_valid' => null], $result);
    }

    public function test_it_trims_and_url_decodes_the_value(): void
    {
        // Arrange

        $action = resolve(DecryptValueAction::class);

        // Act

        $result = $action->execute(' '.urlencode(Crypt::encryptString('secret')).' ');

        // Assert

        $this->assertSame('secret', $result['value']);
    }

    public function test_it_unwraps_a_serialized_string(): void
    {
        // Arrange

        $action = resolve(DecryptValueAction::class);

        // Act

        $result = $action->execute(Crypt::encrypt('abc'));

        // Assert

        $this->assertSame('abc', $result['value']);
    }

    public function test_it_removes_a_valid_cookie_prefix(): void
    {
        // Arrange

        $encrypted = Crypt::encryptString(CookieValuePrefix::create('theme', Crypt::getKey()).'dark');

        $action = resolve(DecryptValueAction::class);

        // Act

        $result = $action->execute($encrypted, 'theme');

        // Assert

        $this->assertSame(['value' => 'dark', 'prefix_valid' => true], $result);
    }

    public function test_it_flags_a_cookie_prefix_made_for_another_cookie(): void
    {
        // Arrange

        $prefixed = CookieValuePrefix::create('theme', Crypt::getKey()).'dark';

        $action = resolve(DecryptValueAction::class);

        // Act

        $result = $action->execute(Crypt::encryptString($prefixed), 'other');

        // Assert

        $this->assertSame(['value' => $prefixed, 'prefix_valid' => false], $result);
    }

    public function test_it_removes_a_prefix_without_a_cookie_name(): void
    {
        // Arrange

        $encrypted = Crypt::encryptString(CookieValuePrefix::create('session', Crypt::getKey()).'abc');

        $action = resolve(DecryptValueAction::class);

        // Act

        $result = $action->execute($encrypted);

        // Assert

        $this->assertSame(['value' => 'abc', 'prefix_valid' => null], $result);
    }

    public function test_it_breaks_when_the_value_cannot_be_decrypted(): void
    {
        // Arrange

        $action = resolve(DecryptValueAction::class);

        // Anticipate

        $this->expectException(InvalidToolInputException::class);
        $this->expectExceptionMessage('The value cannot be decrypted with the app key.');

        // Act

        $action->execute('nope');
    }
}
