<?php

declare(strict_types=1);

namespace Sunchayn\Nimbus\Tests\App\Modules\Magic\Toolbox\Actions;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\URL;
use PHPUnit\Framework\Attributes\CoversClass;
use Sunchayn\Nimbus\Modules\Magic\Toolbox\Actions\CreateSignedUrlAction;
use Sunchayn\Nimbus\Modules\Magic\Toolbox\DataTransferObjects\CreateSignedUrlData;
use Sunchayn\Nimbus\Modules\Magic\Toolbox\Exceptions\InvalidToolInputException;
use Sunchayn\Nimbus\Tests\TestCase;

#[CoversClass(CreateSignedUrlAction::class)]
class CreateSignedUrlActionFunctionalTest extends TestCase
{
    public function test_it_creates_a_signed_url(): void
    {
        // Arrange

        Route::get('/_toolbox/{user}', fn () => 'ok')->name('toolbox.signed');

        $action = resolve(CreateSignedUrlAction::class);

        // Act

        $url = $action->execute(new CreateSignedUrlData('toolbox.signed', ['user' => 5], null));

        // Assert

        $this->assertStringContainsString('/_toolbox/5', $url);
        $this->assertStringNotContainsString('expires=', $url);
        $this->assertTrue(URL::hasCorrectSignature(Request::create($url)));
    }

    public function test_it_creates_a_url_that_expires(): void
    {
        // Arrange

        $this->freezeTime();

        Route::get('/_toolbox/{user}', fn () => 'ok')->name('toolbox.signed');

        $action = resolve(CreateSignedUrlAction::class);

        // Act

        $url = $action->execute(new CreateSignedUrlData('toolbox.signed', ['user' => 5], 10));

        // Assert

        $this->assertStringContainsString('expires='.now()->addMinutes(10)->getTimestamp(), $url);
    }

    public function test_it_breaks_when_the_route_does_not_exist(): void
    {
        // Arrange

        $action = resolve(CreateSignedUrlAction::class);

        // Anticipate

        $this->expectException(InvalidToolInputException::class);
        $this->expectExceptionMessage('The route `missing` does not exist.');

        // Act

        $action->execute(new CreateSignedUrlData('missing', [], null));
    }

    public function test_it_breaks_when_a_route_parameter_is_missing(): void
    {
        // Arrange

        Route::get('/_toolbox/{user}', fn () => 'ok')->name('toolbox.signed');

        $action = resolve(CreateSignedUrlAction::class);

        // Anticipate

        $this->expectException(InvalidToolInputException::class);

        // Act

        $action->execute(new CreateSignedUrlData('toolbox.signed', [], null));
    }
}
