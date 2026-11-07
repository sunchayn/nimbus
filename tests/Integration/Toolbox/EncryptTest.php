<?php

declare(strict_types=1);

namespace Sunchayn\Nimbus\Tests\Integration\Toolbox;

use Illuminate\Support\Facades\Crypt;
use PHPUnit\Framework\Attributes\CoversClass;
use Sunchayn\Nimbus\Http\Api\Toolbox\ToolboxController;
use Sunchayn\Nimbus\Modules\Magic\Toolbox\Actions\EncryptValueAction;
use Sunchayn\Nimbus\Tests\TestCase;

#[CoversClass(ToolboxController::class)]
class EncryptTest extends TestCase
{
    public function test_it_requires_a_value(): void
    {
        // Act

        $response = $this->postJson(route('nimbus.api.toolbox.encrypt'));

        // Assert

        $response->assertUnprocessable()->assertJsonValidationErrors('value');
    }

    public function test_it_returns_the_result_of_the_action(): void
    {
        // Arrange

        $actionSpy = $this->spy(EncryptValueAction::class);

        // Anticipate

        $actionSpy->shouldReceive('execute')->andReturn('::encrypted::');

        // Act

        $response = $this->postJson(route('nimbus.api.toolbox.encrypt'), ['value' => 'dark', 'cookie_name' => 'theme']);

        // Assert

        $response->assertOk()->assertExactJson(['value' => '::encrypted::']);

        $actionSpy
            ->shouldHaveReceived('execute')
            ->once()
            ->withArgs(function (string $value, ?string $cookieName): bool {
                $this->assertSame('dark', $value);
                $this->assertSame('theme', $cookieName);

                return true;
            });
    }

    public function test_it_integrates(): void
    {
        // Act

        $response = $this->postJson(route('nimbus.api.toolbox.encrypt'), ['value' => 'dark']);

        // Assert

        $response->assertOk();

        $this->assertSame('dark', Crypt::decryptString($response->json('value')));
    }
}
