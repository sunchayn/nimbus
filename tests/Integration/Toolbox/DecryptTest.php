<?php

declare(strict_types=1);

namespace Sunchayn\Nimbus\Tests\Integration\Toolbox;

use Illuminate\Support\Facades\Crypt;
use PHPUnit\Framework\Attributes\CoversClass;
use Sunchayn\Nimbus\Http\Api\Toolbox\ToolboxController;
use Sunchayn\Nimbus\Modules\Magic\Toolbox\Actions\DecryptValueAction;
use Sunchayn\Nimbus\Modules\Magic\Toolbox\Exceptions\InvalidToolInputException;
use Sunchayn\Nimbus\Tests\TestCase;

#[CoversClass(ToolboxController::class)]
class DecryptTest extends TestCase
{
    public function test_it_requires_a_value(): void
    {
        // Act

        $response = $this->postJson(route('nimbus.api.toolbox.decrypt'));

        // Assert

        $response->assertUnprocessable()->assertJsonValidationErrors('value');
    }

    public function test_it_returns_the_result_of_the_action(): void
    {
        // Arrange

        $actionSpy = $this->spy(DecryptValueAction::class);

        // Anticipate

        $actionSpy->shouldReceive('execute')->andReturn(['value' => 'secret', 'prefix_valid' => true]);

        // Act

        $response = $this->postJson(route('nimbus.api.toolbox.decrypt'), ['value' => '::payload::', 'cookie_name' => 'theme']);

        // Assert

        $response->assertOk()->assertExactJson(['value' => 'secret', 'prefix_valid' => true]);

        $actionSpy
            ->shouldHaveReceived('execute')
            ->once()
            ->withArgs(function (string $value, ?string $cookieName): bool {
                $this->assertSame('::payload::', $value);
                $this->assertSame('theme', $cookieName);

                return true;
            });
    }

    public function test_it_reports_a_value_that_cannot_be_decrypted(): void
    {
        // Arrange

        $actionSpy = $this->spy(DecryptValueAction::class);

        // Anticipate

        $actionSpy->shouldReceive('execute')->andThrow(InvalidToolInputException::becauseValueCannotBeDecrypted());

        // Act

        $response = $this->postJson(route('nimbus.api.toolbox.decrypt'), ['value' => 'nope']);

        // Assert

        $response->assertUnprocessable()->assertExactJson(['message' => 'The value cannot be decrypted with the app key.']);
    }

    public function test_it_integrates(): void
    {
        // Arrange

        $encrypted = Crypt::encryptString('secret');

        // Act

        $response = $this->postJson(route('nimbus.api.toolbox.decrypt'), ['value' => $encrypted]);

        // Assert

        $response->assertOk()->assertExactJson(['value' => 'secret', 'prefix_valid' => null]);
    }
}
