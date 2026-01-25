<?php

namespace Sunchayn\Nimbus\Tests\App\Modules\Relay\Authorization\Handlers;

use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Contracts\Auth\UserProvider;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Http\Request;
use Illuminate\Session\SessionManager;
use Illuminate\Session\Store;
use Mockery\MockInterface;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\CoversMethod;
use Sunchayn\Nimbus\Modules\Config\ActiveApplicationResolver;
use Sunchayn\Nimbus\Modules\Relay\Authorization\Exceptions\InvalidAuthorizationValueException;
use Sunchayn\Nimbus\Modules\Relay\Authorization\Handlers\CurrentUserAuthorizationHandler;
use Sunchayn\Nimbus\Tests\App\Modules\Relay\Authorization\Handlers\Shared\HandlesRecallerCookies;
use Sunchayn\Nimbus\Tests\App\Modules\Relay\Authorization\Handlers\Stubs\DummyAuthenticatable;
use Sunchayn\Nimbus\Tests\App\Modules\Relay\Authorization\Handlers\Stubs\DummySpecialAuthenticationInjector;
use Sunchayn\Nimbus\Tests\TestCase;

#[CoversClass(CurrentUserAuthorizationHandler::class)]
#[CoversMethod(InvalidAuthorizationValueException::class, 'becauseCookieIsNotDecryptable')]
class CurrentUserAuthorizationHandlerFunctionalTest extends TestCase
{
    use HandlesRecallerCookies;

    public function test_it_uses_special_authentication_injector_to_authorize_request(): void
    {
        // Arrange

        $this->mock(ActiveApplicationResolver::class, function (MockInterface $mock) use (&$guardName) {
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

    public function test_it_retrieves_user_from_session_cookie_when_request_user_is_null(): void
    {
        // Arrange

        $this->mock(ActiveApplicationResolver::class, function (MockInterface $mock) use (&$guardName) {
            $mock->shouldReceive('getAuthGuard')->andReturn($guardName = 'web');
            $mock->shouldReceive('getSpecialAuthInjector')->andReturn(DummySpecialAuthenticationInjector::class);
        });

        $dummyAuthenticatable = new DummyAuthenticatable(id: $userId = fake()->randomNumber());

        $this->mockAuthManagerToUseDummyModel($userId, $dummyAuthenticatable, $guardName);

        $encrypter = resolve('encrypter');
        $sessionId = fake()->uuid();
        $sessionCookieName = config('session.cookie');

        // Create encrypted session cookie value
        $cookieValue = $encrypter->encrypt($sessionId . '|' . $sessionId, serialize: false);

        $relayRequest = Request::create('ping');
        $relayRequest->cookies->set($sessionCookieName, $cookieValue);
        $relayRequest->setUserResolver(fn () => null);

        // Mock session to return user ID
        $sessionMock = $this->mock(Store::class);
        $sessionMock->shouldReceive('setId')->with($sessionId)->once();
        $sessionMock->shouldReceive('start')->once();
        $sessionMock->shouldReceive('all')->andReturn([
            'login_web' => $userId,
            'other_key' => 'other_value',
        ]);

        $sessionManagerMock = $this->mock(SessionManager::class);
        $sessionManagerMock->shouldReceive('driver')->andReturn($sessionMock);

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
                $this->assertSame($dummyAuthenticatable, $authenticatable);
                $this->assertSame($pendingRequestArg, $pendingRequest);

                return true;
            });
    }

    public function test_it_returns_unmodified_request_when_session_cookie_is_invalid(): void
    {
        // Arrange

        $sessionCookieName = config('session.cookie');

        $relayRequest = Request::create('ping');
        $relayRequest->cookies->set($sessionCookieName, 123); // Non-string cookie value
        $relayRequest->setUserResolver(fn () => null);

        $handler = resolve(CurrentUserAuthorizationHandler::class, [
            'relayRequest' => $relayRequest,
        ]);

        $pendingRequest = resolve(PendingRequest::class);

        // Act

        $pendingRequestResponse = $handler->authorize($pendingRequest);

        // Assert

        $this->assertSame($pendingRequest, $pendingRequestResponse);
    }

    public function test_it_throws_exception_when_session_cookie_decryption_fails(): void
    {
        // Arrange

        $sessionCookieName = config('session.cookie');

        $relayRequest = Request::create('ping');
        $relayRequest->cookies->set($sessionCookieName, 'invalid-encrypted-value');
        $relayRequest->setUserResolver(fn () => null);

        $handler = resolve(CurrentUserAuthorizationHandler::class, [
            'relayRequest' => $relayRequest,
        ]);

        $pendingRequest = resolve(PendingRequest::class);

        // Assert

        $this->expectException(InvalidAuthorizationValueException::class);
        $this->expectExceptionCode(InvalidAuthorizationValueException::REMEMBER_ME_COOKIE_IS_CORRUPT);
        $this->expectExceptionMessage('The Current User remember me cookie is corrupt. Reload the page, or pick a different authorization method.');

        // Act

        $handler->authorize($pendingRequest);
    }

    public function test_it_returns_unmodified_request_when_no_user_found_in_session(): void
    {
        // Arrange

        $this->mock(ActiveApplicationResolver::class, function (MockInterface $mock) use (&$guardName) {
            $mock->shouldReceive('getAuthGuard')->andReturn($guardName = 'web');
        });

        $userProvider = $this->mock(UserProvider::class);
        $userProvider->shouldReceive('retrieveById')->with(null)->andReturn(null);

        $this->mockAuthManager($userProvider, $guardName);

        $encrypter = resolve('encrypter');
        $sessionId = fake()->uuid();
        $sessionCookieName = config('session.cookie');

        $cookieValue = $encrypter->encrypt($sessionId . '|' . $sessionId, serialize: false);

        $relayRequest = Request::create('ping');
        $relayRequest->cookies->set($sessionCookieName, $cookieValue);
        $relayRequest->setUserResolver(fn () => null);

        // Mock session with no login data
        $sessionMock = $this->mock(Store::class);
        $sessionMock->shouldReceive('setId')->with($sessionId)->once();
        $sessionMock->shouldReceive('start')->once();
        $sessionMock->shouldReceive('all')->andReturn([
            'other_key' => 'other_value',
        ]);

        $sessionManagerMock = $this->mock(SessionManager::class);
        $sessionManagerMock->shouldReceive('driver')->andReturn($sessionMock);

        $handler = resolve(CurrentUserAuthorizationHandler::class, [
            'relayRequest' => $relayRequest,
        ]);

        $pendingRequest = resolve(PendingRequest::class);

        // Act

        $pendingRequestResponse = $handler->authorize($pendingRequest);

        // Assert

        $this->assertSame($pendingRequest, $pendingRequestResponse);
    }
}
