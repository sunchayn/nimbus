<?php

declare(strict_types=1);

namespace Sunchayn\Nimbus\Tests\Integration\Toolbox;

use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\URL;
use PHPUnit\Framework\Attributes\CoversClass;
use Sunchayn\Nimbus\Http\Api\Toolbox\ToolboxController;
use Sunchayn\Nimbus\Modules\Magic\Toolbox\Actions\VerifySignedUrlAction;
use Sunchayn\Nimbus\Tests\TestCase;

#[CoversClass(ToolboxController::class)]
class VerifySignedUrlTest extends TestCase
{
    public function test_it_requires_a_url(): void
    {
        // Act

        $response = $this->postJson(route('nimbus.api.toolbox.signed-url.verify'));

        // Assert

        $response->assertUnprocessable()->assertJsonValidationErrors('url');
    }

    public function test_it_returns_the_result_of_the_action(): void
    {
        // Arrange

        $actionSpy = $this->spy(VerifySignedUrlAction::class);

        // Anticipate

        $actionSpy->shouldReceive('execute')->andReturn(['valid' => true, 'correct_signature' => true, 'expired' => false]);

        // Act

        $response = $this->postJson(route('nimbus.api.toolbox.signed-url.verify'), ['url' => 'http://app.test/x']);

        // Assert

        $response->assertOk()->assertExactJson(['valid' => true, 'correct_signature' => true, 'expired' => false]);

        $actionSpy
            ->shouldHaveReceived('execute')
            ->once()
            ->withArgs(function (string $url): bool {
                $this->assertSame('http://app.test/x', $url);

                return true;
            });
    }

    public function test_it_integrates(): void
    {
        // Arrange

        Route::get('/_toolbox/{user}', fn () => 'ok')->name('toolbox.signed');

        $url = URL::signedRoute('toolbox.signed', ['user' => 5]);

        // Act

        $response = $this->postJson(route('nimbus.api.toolbox.signed-url.verify'), ['url' => $url]);

        // Assert

        $response->assertOk()->assertExactJson(['valid' => true, 'correct_signature' => true, 'expired' => false]);
    }
}
