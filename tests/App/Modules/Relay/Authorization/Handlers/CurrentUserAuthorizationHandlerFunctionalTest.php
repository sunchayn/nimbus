<?php

namespace Sunchayn\Nimbus\Tests\App\Modules\Relay\Authorization\Handlers;

use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Http\Request;
use Mockery\MockInterface;
use PHPUnit\Framework\Attributes\CoversClass;
use Sunchayn\Nimbus\Modules\Relay\Authorization\Handlers\CurrentUserAuthorizationHandler;
use Sunchayn\Nimbus\Tests\App\Modules\Relay\Authorization\Handlers\Shared\HandlesRecallerCookies;
use Sunchayn\Nimbus\Tests\App\Modules\Relay\Authorization\Handlers\Stubs\DummyAuthenticatable;
use Sunchayn\Nimbus\Tests\App\Modules\Relay\Authorization\Handlers\Stubs\DummySpecialAuthenticationInjector;
use Sunchayn\Nimbus\Tests\TestCase;

#[CoversClass(CurrentUserAuthorizationHandler::class)]
class CurrentUserAuthorizationHandlerFunctionalTest extends TestCase
{
    use HandlesRecallerCookies;

    public function test_it_uses_special_authentication_injector_to_authorize_request(): void
    {
        // Arrange

        $this->mock(\Sunchayn\Nimbus\Modules\Config\ActiveApplicationResolver::class, function (MockInterface $mock) use (&$guardName) {
            $mock->shouldReceive('getAuthGuard')->andReturn($guardName = fake()->word());
            $mock->shouldReceive('getSpecialAuthInjector')->andReturn(DummySpecialAuthenticationInjector::class);
        });
        $dummyAuthenticatable = new DummyAuthenticatable(id: $userId = fake()->randomNumber());

        $this->mockAuthManagerToUseDummyModel($userId, $dummyAuthenticatable, $guardName);

        $relayRequest = Request::create('ping');
        $relayRequest->setUserResolver(fn () => $dummyAuthenticatable);

        $dummySpecialAuthenticationInjectorMock = $this->mock(DummySpecialAuthenticationInjector::class);

        $handler = resolve(CurrentUserAuthorizationHandler::class, [
            'relayRequest' => $relayRequest,
        ]);

        $pendingRequest = resolve(PendingRequest::class);

        // Anticipate

        $dummySpecialAuthenticationInjectorMock
            ->shouldReceive('attach')
            ->withAnyArgs()
            ->andReturn($pendingRequest);

        // Act

        $responsePendingRequest = $handler->authorize($pendingRequest);

        // Assert

        $this->assertSame($pendingRequest, $responsePendingRequest);

        $dummySpecialAuthenticationInjectorMock
            ->shouldHaveReceived('attach')
            ->once()
            ->withArgs(function (PendingRequest $pendingRequestArg, Authenticatable $authenticatable) use ($pendingRequest, $dummyAuthenticatable) {
                $this->assertSame(
                    $dummyAuthenticatable,
                    $authenticatable,
                );

                $this->assertSame($pendingRequestArg, $pendingRequest);

                return true;
            });
    }

    public function test_it_returns_unmodified_request_when_no_cookies(): void
    {
        // Arrange

        $relayRequest = Request::create('ping');
        $relayRequest->headers->set('HOST', fake()->domainName());

        $handler = resolve(
            CurrentUserAuthorizationHandler::class,
            [
                'relayRequest' => $relayRequest,
            ],
        );

        $pendingRequest = resolve(PendingRequest::class);

        // Act

        $pendingRequestResponse = $handler->authorize($pendingRequest);

        // Assert

        $cookies = $pendingRequestResponse->getOptions()['cookies'] ?? [];

        $this->assertEmpty($cookies);

        $this->assertSame($pendingRequest, $pendingRequestResponse);
    }
}
