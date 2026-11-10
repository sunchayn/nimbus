<?php

declare(strict_types=1);

namespace Sunchayn\Nimbus\Tests\App\Modules\Magic\Toolbox\Actions;

use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\URL;
use PHPUnit\Framework\Attributes\CoversClass;
use Sunchayn\Nimbus\Modules\Magic\Toolbox\Actions\VerifySignedUrlAction;
use Sunchayn\Nimbus\Tests\TestCase;

#[CoversClass(VerifySignedUrlAction::class)]
class VerifySignedUrlActionFunctionalTest extends TestCase
{
    public function test_it_accepts_a_correctly_signed_url(): void
    {
        // Arrange

        Route::get('/_toolbox/{user}', fn () => 'ok')->name('toolbox.signed');

        $action = resolve(VerifySignedUrlAction::class);

        // Act

        $result = $action->execute(URL::signedRoute('toolbox.signed', ['user' => 5]));

        // Assert

        $this->assertSame(['valid' => true, 'correct_signature' => true, 'expired' => false], $result);
    }

    public function test_it_rejects_a_tampered_url(): void
    {
        // Arrange

        Route::get('/_toolbox/{user}', fn () => 'ok')->name('toolbox.signed');

        $url = URL::signedRoute('toolbox.signed', ['user' => 5]);

        $action = resolve(VerifySignedUrlAction::class);

        // Act

        $result = $action->execute(str_replace('/5', '/6', $url));

        // Assert

        $this->assertFalse($result['valid']);
        $this->assertFalse($result['correct_signature']);
    }

    public function test_it_rejects_an_unsigned_url(): void
    {
        // Arrange

        $action = resolve(VerifySignedUrlAction::class);

        // Act

        $result = $action->execute('http://localhost/_toolbox/5');

        // Assert

        $this->assertFalse($result['valid']);
    }

    public function test_it_reports_an_expired_url_with_a_correct_signature(): void
    {
        // Arrange

        Route::get('/_toolbox/expired', fn () => 'ok')->name('toolbox.expired');

        $url = URL::temporarySignedRoute('toolbox.expired', now()->subMinute());

        $action = resolve(VerifySignedUrlAction::class);

        // Act

        $result = $action->execute($url);

        // Assert

        $this->assertSame(['valid' => false, 'correct_signature' => true, 'expired' => true], $result);
    }
}
