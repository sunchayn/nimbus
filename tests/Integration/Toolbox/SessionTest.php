<?php

declare(strict_types=1);

namespace Sunchayn\Nimbus\Tests\Integration\Toolbox;

use Illuminate\Support\Facades\Session;
use PHPUnit\Framework\Attributes\CoversClass;
use Sunchayn\Nimbus\Http\Api\Toolbox\ToolboxController;
use Sunchayn\Nimbus\Modules\Magic\Toolbox\Actions\ReadSessionAction;
use Sunchayn\Nimbus\Modules\Magic\Toolbox\Exceptions\InvalidToolInputException;
use Sunchayn\Nimbus\Tests\TestCase;

#[CoversClass(ToolboxController::class)]
class SessionTest extends TestCase
{
    public function test_it_requires_a_value(): void
    {
        // Act

        $response = $this->postJson(route('nimbus.api.toolbox.session'));

        // Assert

        $response->assertUnprocessable()->assertJsonValidationErrors('value');
    }

    public function test_it_returns_the_result_of_the_action(): void
    {
        // Arrange

        $actionSpy = $this->spy(ReadSessionAction::class);

        // Anticipate

        $actionSpy->shouldReceive('execute')->andReturn(['user' => 'jane']);

        // Act

        $response = $this->postJson(route('nimbus.api.toolbox.session'), ['value' => '::cookie::']);

        // Assert

        $response->assertOk()->assertExactJson(['session' => ['user' => 'jane']]);

        $actionSpy
            ->shouldHaveReceived('execute')
            ->once()
            ->withArgs(function (string $cookieValue): bool {
                $this->assertSame('::cookie::', $cookieValue);

                return true;
            });
    }

    public function test_it_reports_an_unknown_session(): void
    {
        // Arrange

        $actionSpy = $this->spy(ReadSessionAction::class);

        // Anticipate

        $actionSpy->shouldReceive('execute')->andThrow(InvalidToolInputException::becauseSessionIsUnknown());

        // Act

        $response = $this->postJson(route('nimbus.api.toolbox.session'), ['value' => '::cookie::']);

        // Assert

        $response->assertUnprocessable()->assertExactJson(['message' => 'No stored session matches this value.']);
    }

    public function test_it_integrates(): void
    {
        // Arrange

        config()->set('session.driver', 'array');

        $store = Session::driver();
        $store->setId(str_repeat('a', 40));
        $store->put('user', 'jane');
        $store->save();

        // Act

        $response = $this->postJson(route('nimbus.api.toolbox.session'), ['value' => str_repeat('a', 40)]);

        // Assert

        $response->assertOk()->assertJsonPath('session.user', 'jane');
    }
}
