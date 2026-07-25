<?php

namespace Sunchayn\Nimbus\Tests\App\Modules\Relay\Services\Authorization\Injectors;

use Illuminate\Auth\SessionGuard;
use Illuminate\Container\Container;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Contracts\Auth\UserProvider;
use Illuminate\Encryption\Encrypter;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Http\Request;
use Mockery;
use PHPUnit\Framework\Attributes\CoversClass;
use Sunchayn\Nimbus\Modules\Config\ActiveApplicationResolver;
use Sunchayn\Nimbus\Modules\Relay\Services\Authorization\Injectors\RememberMeCookieInjector;
use Sunchayn\Nimbus\Tests\App\Modules\Relay\Authorization\Injectors\MockInterface;
use Sunchayn\Nimbus\Tests\TestCase;

#[CoversClass(RememberMeCookieInjector::class)]
class RememberMeCookieInjectorUnitTest extends TestCase
{
    private Container $containerMock;

    private ActiveApplicationResolver|MockInterface $projectManagerMock;

    private SessionGuard $authGuardMock;

    private UserProvider $userProviderMock;

    private Encrypter $encrypterMock;

    protected function setUp(): void
    {
        parent::setUp();

        $this->containerMock = Mockery::mock(Container::class);
        $this->projectManagerMock = Mockery::mock(ActiveApplicationResolver::class);
        $this->authGuardMock = Mockery::mock(SessionGuard::class);
        $this->userProviderMock = Mockery::mock(UserProvider::class);
        $this->encrypterMock = Mockery::mock(Encrypter::class);
    }

    public function test_it_generates_and_attaches_remember_me_cookie_for_new_user(): void
    {
        // Arrange

        $authenticatable = $this->createAuthenticatable(
            id: 123,
            rememberToken: 'existing_token',
            passwordField: 'password'
        );

        $relayRequest = Request::create('ping', server: ['HTTP_HOST' => 'example.com']);
        $relayRequest->setUserResolver(fn () => null); // <- no authenticated user in relay request

        $this->authGuardMock
            ->shouldReceive('getRecallerName')
            ->andReturn('remember_web');

        $this->encrypterMock
            ->shouldReceive('getKey')
            ->andReturn('base64:test-key');

        $this->encrypterMock
            ->shouldReceive('encrypt')
            ->withArgs(function (mixed $value, bool $serialize) {
                $this->assertStringEndsWith(
                    '|123|existing_token|password',
                    $value,
                );

                $this->assertFalse($serialize);

                return true;
            })
            ->once()
            ->andReturn('encrypted_recaller_token');

        $pendingRequestMock = Mockery::mock(PendingRequest::class);

        $injector = $this->instantiateInjector($relayRequest);

        // Anticipate

        $pendingRequestMock
            ->shouldReceive('withCookies')
            ->once()
            ->withArgs(function (array $withCookiesArg, string $domain) {
                $this->assertEquals(
                    ['remember_web' => 'encrypted_recaller_token'],
                    $withCookiesArg,
                );

                $this->assertEquals(
                    'example.com',
                    $domain,
                );

                return true;
            })
            ->andReturnSelf();

        // Act

        $result = $injector->attach($pendingRequestMock, $authenticatable);

        // Assert

        $this->assertSame($pendingRequestMock, $result);
    }

    public function test_it_generates_new_remember_token_when_user_has_none(): void
    {
        // Arrange

        $authenticatable = $this->createAuthenticatable(
            id: 456,
            rememberToken: null, // <- no existing token
            passwordField: 'password'
        );

        $relayRequest = Request::create('ping', server: ['HTTP_HOST' => 'example.com']);
        $relayRequest->setUserResolver(fn () => null);

        $this->authGuardMock
            ->shouldReceive('getRecallerName')
            ->andReturn('remember_web');

        $this->encrypterMock
            ->shouldReceive('getKey')
            ->andReturn('base64:test-key');

        $this->encrypterMock
            ->shouldReceive('encrypt')
            ->andReturn('encrypted_recaller_token');

        $pendingRequestMock = Mockery::mock(PendingRequest::class);

        $pendingRequestMock
            ->shouldReceive('withCookies')
            ->andReturnSelf();

        $injector = $this->instantiateInjector($relayRequest);

        // Anticipate

        $this->userProviderMock->expects('updateRememberToken');

        // Act

        $injector->attach($pendingRequestMock, $authenticatable);

        // Assert

        $this
            ->userProviderMock
            ->shouldHaveReceived('updateRememberToken')
            ->once()
            ->withArgs(function (Authenticatable $user, string $token) {
                $this->assertSame(456, $user->getAuthIdentifier());
                $this->assertMatchesRegularExpression('/^[a-f0-9]{64}$/', $token);

                return true;
            });
    }

    public function test_it_forwards_existing_remember_me_cookie_for_same_user(): void
    {
        // Arrange

        $currentUser = $this->createAuthenticatable(
            id: 789,
            rememberToken: 'current_token',
            passwordField: 'password'
        );

        $targetUser = $this->createAuthenticatable(
            id: 789, // <- same ID as current user
            rememberToken: 'target_token',
            passwordField: 'password'
        );

        $relayRequest = Request::create('ping', server: ['HTTP_HOST' => 'example.com']);
        $relayRequest->setUserResolver(fn () => $currentUser);
        $relayRequest->cookies->set('remember_web', $encrytpedCookieValue = sha1('::value::'));

        $this->authGuardMock
            ->shouldReceive('getRecallerName')
            ->andReturn('remember_web');

        $this->encrypterMock
            ->shouldReceive('getKey')
            ->andReturn('base64:test-key');

        $pendingRequestMock = Mockery::mock(PendingRequest::class);

        $injector = $this->instantiateInjector($relayRequest);

        // Anticipate

        $this->encrypterMock->shouldNotReceive('encrypt');

        $pendingRequestMock
            ->expects('withCookies')
            ->andReturnSelf();

        // Act

        $result = $injector->attach($pendingRequestMock, $targetUser);

        // Assert

        $this->assertSame($pendingRequestMock, $result);

        $pendingRequestMock
            ->shouldHaveReceived('withCookies')
            ->once()
            ->withArgs(function (array $cookies, string $domain) use ($encrytpedCookieValue) {
                $this->assertEquals(
                    ['remember_web' => $encrytpedCookieValue],
                    $cookies,
                );

                $this->assertEquals('example.com', $domain);

                return true;
            });
    }

    public function test_it_does_not_forward_cookie_when_users_differ(): void
    {
        // Arrange

        $currentUser = $this->createAuthenticatable(
            id: 100,
            rememberToken: 'current_token',
            passwordField: 'password'
        );

        $targetUser = $this->createAuthenticatable(
            id: 200, // <- different ID
            rememberToken: 'target_token',
            passwordField: 'password2'
        );

        $relayRequest = Request::create('ping', server: ['HTTP_HOST' => 'example.com']);
        $relayRequest->setUserResolver(fn () => $currentUser);
        $relayRequest->cookies->set('remember_web', 'existing_cookie_value');

        $this->authGuardMock
            ->shouldReceive('getRecallerName')
            ->andReturn('remember_web');

        $this->encrypterMock
            ->shouldReceive('getKey')
            ->andReturn('base64:test-key');

        $pendingRequestMock = Mockery::mock(PendingRequest::class);

        $injector = $this->instantiateInjector($relayRequest);

        // Anticipate

        $this->encrypterMock
            ->shouldReceive('encrypt')
            ->once()
            ->withArgs(function (mixed $value, bool $serialize) {
                $this->assertStringEndsWith('|200|target_token|password2', $value);
                $this->assertFalse($serialize);

                return true;
            })
            ->andReturn('encrypted_new_cookie');

        $pendingRequestMock
            ->shouldReceive('withCookies')
            ->once()
            ->withArgs(function (array $cookies, string $domain) {
                $this->assertEquals(
                    ['remember_web' => 'encrypted_new_cookie'],
                    $cookies,
                );

                $this->assertEquals('example.com', $domain);

                return true;
            })
            ->andReturnSelf();

        // Act

        $result = $injector->attach($pendingRequestMock, $targetUser);

        // Assert

        $this->assertSame($pendingRequestMock, $result);
    }

    public function test_it_does_not_forward_when_no_cookie_exists(): void
    {
        // Arrange

        $currentUser = $this->createAuthenticatable(
            id: 100,
            rememberToken: 'current_token',
            passwordField: 'password'
        );

        $targetUser = $this->createAuthenticatable(
            id: 100,
            rememberToken: 'target_token',
            passwordField: 'password'
        );

        $relayRequest = Request::create('ping', server: ['HTTP_HOST' => 'example.com']);
        $relayRequest->setUserResolver(fn () => $currentUser);
        // No cookie set

        $this->authGuardMock
            ->shouldReceive('getRecallerName')
            ->andReturn('remember_web');

        $this->encrypterMock
            ->shouldReceive('getKey')
            ->andReturn('base64:test-key');

        $pendingRequestMock = Mockery::mock(PendingRequest::class);

        $injector = $this->instantiateInjector($relayRequest);

        // Anticipate

        $this->encrypterMock
            ->shouldReceive('encrypt')
            ->once()
            ->withArgs(function (mixed $value, bool $serialize) {
                $this->assertStringEndsWith('|100|target_token|password', $value);
                $this->assertFalse($serialize);

                return true;
            })
            ->andReturn('encrypted_new_cookie');

        $pendingRequestMock
            ->shouldReceive('withCookies')
            ->once()
            ->andReturnSelf();

        // Act

        $result = $injector->attach($pendingRequestMock, $targetUser);

        // Assert

        $this->assertSame($pendingRequestMock, $result);
    }

    /*
     * Helpers.
     */

    private function instantiateInjector(Request $relayRequest): RememberMeCookieInjector
    {
        $this->projectManagerMock
            ->shouldReceive('getAuthGuard')
            ->andReturn('web');

        $authManagerMock = Mockery::mock(\Illuminate\Auth\AuthManager::class);
        $authManagerMock
            ->shouldReceive('guard')
            ->with('web')
            ->andReturn($this->authGuardMock);

        $this->authGuardMock
            ->shouldReceive('getProvider')
            ->andReturn($this->userProviderMock);

        $this->containerMock
            ->shouldReceive('get')
            ->with('encrypter')
            ->andReturn($this->encrypterMock);

        $this->containerMock
            ->shouldReceive('get')
            ->with('auth')
            ->andReturn($authManagerMock);

        return new RememberMeCookieInjector(
            relayRequest: $relayRequest,
            container: $this->containerMock,
            activeApplicationResolver: $this->projectManagerMock,
        );
    }

    private function createAuthenticatable(
        int $id,
        ?string $rememberToken,
        string $passwordField
    ): Authenticatable {
        $authenticatable = Mockery::mock(Authenticatable::class);

        $authenticatable
            ->shouldReceive('getAuthIdentifier')
            ->andReturn($id);

        $authenticatable
            ->shouldReceive('getRememberToken')
            ->andReturn($rememberToken);

        $authenticatable
            ->shouldReceive('getAuthPasswordName')
            ->andReturn($passwordField);

        return $authenticatable;
    }
}
