<?php

namespace Sunchayn\Nimbus\Tests\App\Modules\Magic\Listeners;

use Illuminate\Auth\GenericUser;
use Illuminate\Auth\RequestGuard;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Contracts\Auth\UserProvider;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;
use PHPUnit\Framework\Attributes\CoversClass;
use Sunchayn\Nimbus\Modules\Magic\Listeners\ActorHeaderListener;
use Sunchayn\Nimbus\Modules\Magic\ValueObjects\NimbusHeaders;
use Sunchayn\Nimbus\Tests\TestCase;

#[CoversClass(ActorHeaderListener::class)]
class ActorHeaderListenerFunctionalTest extends TestCase
{
    public const ULID = '01HZX3Y8K2M4N6P8Q0R2S4T6V8';

    protected function defineEnvironment($app): void
    {
        $app['config']->set('nimbus.allowed_envs', ['testing']);
        $app['config']->set('auth.providers.memory', ['driver' => 'memory']);
        $app['config']->set('auth.guards.custom', ['driver' => 'custom', 'provider' => 'memory']);
    }

    protected function setUp(): void
    {
        parent::setUp();

        Auth::provider('memory', fn (): UserProvider => new class implements UserProvider
        {
            public function retrieveById($identifier): ?Authenticatable
            {
                return $identifier === ActorHeaderListenerFunctionalTest::ULID
                    ? new GenericUser(['id' => $identifier])
                    : null;
            }

            public function retrieveByToken($identifier, $token): ?Authenticatable
            {
                return null;
            }

            public function updateRememberToken(Authenticatable $user, $token): void
            {
                throw new \LogicException('The actor header must not write to the database.');
            }

            public function retrieveByCredentials(array $credentials): ?Authenticatable
            {
                return null;
            }

            public function validateCredentials(Authenticatable $user, array $credentials): bool
            {
                return false;
            }

            public function rehashPasswordIfRequired(Authenticatable $user, array $credentials, bool $force = false): bool
            {
                return false;
            }
        });

        Auth::extend('custom', fn ($app, $name, array $config): RequestGuard => new RequestGuard(
            fn () => null,
            $app['request'],
            Auth::createUserProvider($config['provider']),
        ));

        Route::middleware('auth:custom')->get('/protected', fn () => ['id' => auth()->id()]);
    }

    public function test_it_authenticates_a_guarded_route_with_a_ulid_user(): void
    {
        // Act

        $response = $this->getJson('/protected', [NimbusHeaders::ACTOR => 'custom:'.self::ULID]);

        // Assert

        $response->assertOk()->assertJson(['id' => self::ULID]);
    }

    public function test_it_keeps_the_request_as_a_guest_without_the_header(): void
    {
        // Act & Assert

        $this->getJson('/protected')->assertUnauthorized();
    }

    public function test_it_ignores_an_unknown_user(): void
    {
        // Act & Assert

        $this->getJson('/protected', [NimbusHeaders::ACTOR => 'custom:missing'])->assertUnauthorized();
    }

    public function test_it_ignores_an_unknown_guard(): void
    {
        // Act & Assert

        $this->getJson('/protected', [NimbusHeaders::ACTOR => 'nope:'.self::ULID])->assertUnauthorized();
    }
}
