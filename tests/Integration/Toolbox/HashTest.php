<?php

declare(strict_types=1);

namespace Sunchayn\Nimbus\Tests\Integration\Toolbox;

use Illuminate\Support\Facades\Hash;
use PHPUnit\Framework\Attributes\CoversClass;
use Sunchayn\Nimbus\Http\Api\Toolbox\ToolboxController;
use Sunchayn\Nimbus\Modules\Magic\Toolbox\Actions\HashValueAction;
use Sunchayn\Nimbus\Tests\TestCase;

#[CoversClass(ToolboxController::class)]
class HashTest extends TestCase
{
    public function test_it_requires_a_value(): void
    {
        // Act

        $response = $this->postJson(route('nimbus.api.toolbox.hash'));

        // Assert

        $response->assertUnprocessable()->assertJsonValidationErrors('value');
    }

    public function test_it_returns_the_result_of_the_action(): void
    {
        // Arrange

        $actionSpy = $this->spy(HashValueAction::class);

        // Anticipate

        $actionSpy->shouldReceive('execute')->andReturn(['matches' => true, 'needs_rehash' => false]);

        // Act

        $response = $this->postJson(route('nimbus.api.toolbox.hash'), ['value' => 'password', 'hash' => '::hash::']);

        // Assert

        $response->assertOk()->assertExactJson(['matches' => true, 'needs_rehash' => false]);

        $actionSpy
            ->shouldHaveReceived('execute')
            ->once()
            ->withArgs(function (string $value, ?string $hash): bool {
                $this->assertSame('password', $value);
                $this->assertSame('::hash::', $hash);

                return true;
            });
    }

    public function test_it_integrates(): void
    {
        // Act

        $response = $this->postJson(route('nimbus.api.toolbox.hash'), ['value' => 'password']);

        // Assert

        $response->assertOk();

        $this->assertTrue(Hash::check('password', $response->json('hash')));
    }
}
