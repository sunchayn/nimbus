<?php

declare(strict_types=1);

namespace Sunchayn\Nimbus\Tests\Integration\Toolbox;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\URL;
use PHPUnit\Framework\Attributes\CoversClass;
use Sunchayn\Nimbus\Http\Api\Toolbox\ToolboxController;
use Sunchayn\Nimbus\Modules\Magic\Toolbox\Actions\CreateSignedUrlAction;
use Sunchayn\Nimbus\Modules\Magic\Toolbox\DataTransferObjects\CreateSignedUrlData;
use Sunchayn\Nimbus\Modules\Magic\Toolbox\Exceptions\InvalidToolInputException;
use Sunchayn\Nimbus\Tests\TestCase;

#[CoversClass(ToolboxController::class)]
class CreateSignedUrlTest extends TestCase
{
    public function test_it_requires_a_route(): void
    {
        // Act

        $response = $this->postJson(route('nimbus.api.toolbox.signed-url.create'));

        // Assert

        $response->assertUnprocessable()->assertJsonValidationErrors('route');
    }

    public function test_it_requires_a_positive_expiry(): void
    {
        // Act

        $response = $this->postJson(route('nimbus.api.toolbox.signed-url.create'), ['route' => 'any', 'expires_in_minutes' => 0]);

        // Assert

        $response->assertUnprocessable()->assertJsonValidationErrors('expires_in_minutes');
    }

    public function test_it_passes_the_input_to_the_action(): void
    {
        // Arrange

        $actionSpy = $this->spy(CreateSignedUrlAction::class);

        // Anticipate

        $actionSpy->shouldReceive('execute')->andReturn('http://app.test/signed');

        // Act

        $response = $this->postJson(route('nimbus.api.toolbox.signed-url.create'), [
            'route' => 'toolbox.signed',
            'parameters' => ['user' => 5],
            'expires_in_minutes' => 10,
        ]);

        // Assert

        $response->assertOk()->assertExactJson(['url' => 'http://app.test/signed']);

        $actionSpy
            ->shouldHaveReceived('execute')
            ->once()
            ->withArgs(function (CreateSignedUrlData $data): bool {
                $this->assertSame('toolbox.signed', $data->route);
                $this->assertSame(['user' => 5], $data->parameters);
                $this->assertSame(10, $data->expiresInMinutes);

                return true;
            });
    }

    public function test_it_defaults_the_parameters_and_the_expiry(): void
    {
        // Arrange

        $actionSpy = $this->spy(CreateSignedUrlAction::class);

        // Anticipate

        $actionSpy->shouldReceive('execute')->andReturn('http://app.test/signed');

        // Act

        $this->postJson(route('nimbus.api.toolbox.signed-url.create'), ['route' => 'toolbox.signed']);

        // Assert

        $actionSpy
            ->shouldHaveReceived('execute')
            ->once()
            ->withArgs(function (CreateSignedUrlData $data): bool {
                $this->assertSame([], $data->parameters);
                $this->assertNull($data->expiresInMinutes);

                return true;
            });
    }

    public function test_it_reports_a_route_that_cannot_be_signed(): void
    {
        // Arrange

        $actionSpy = $this->spy(CreateSignedUrlAction::class);

        // Anticipate

        $actionSpy->shouldReceive('execute')->andThrow(InvalidToolInputException::becauseRouteIsUnknown('missing'));

        // Act

        $response = $this->postJson(route('nimbus.api.toolbox.signed-url.create'), ['route' => 'missing']);

        // Assert

        $response->assertUnprocessable()->assertExactJson(['message' => 'The route `missing` does not exist.']);
    }

    public function test_it_integrates(): void
    {
        // Arrange

        Route::get('/_toolbox/{user}', fn () => 'ok')->name('toolbox.signed');

        // Act

        $response = $this->postJson(route('nimbus.api.toolbox.signed-url.create'), [
            'route' => 'toolbox.signed',
            'parameters' => ['user' => 5],
        ]);

        // Assert

        $response->assertOk();

        $this->assertTrue(URL::hasCorrectSignature(Request::create($response->json('url'))));
    }
}
