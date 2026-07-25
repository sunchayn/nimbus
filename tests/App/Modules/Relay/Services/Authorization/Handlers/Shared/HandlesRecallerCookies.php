<?php

namespace Sunchayn\Nimbus\Tests\App\Modules\Relay\Services\Authorization\Handlers\Shared;

use GuzzleHttp\Cookie\SetCookie;
use Illuminate\Auth\AuthManager;
use Illuminate\Contracts\Auth\Guard;
use Illuminate\Contracts\Auth\UserProvider;
use Illuminate\Cookie\CookieValuePrefix;
use Mockery\MockInterface;
use Sunchayn\Nimbus\Tests\App\Modules\Relay\Services\Authorization\Handlers\Stubs\DummyAuthenticatable;

trait HandlesRecallerCookies
{
    private const COOKIE_RECALLER_NAME = 'dummy_recaller';

    /*
     * Mocks.
     */

    private function mockAuthManagerToUseDummyModel(int $userId, ?DummyAuthenticatable $authenticatable, string $guardName): void
    {
        $userProvider = $this->mock(UserProvider::class, function (MockInterface $mock) use ($userId, $authenticatable) {
            $mock
                ->shouldReceive('retrieveById')
                ->with($userId)
                ->andReturn($authenticatable);
        });

        $this->mockAuthManager($userProvider, $guardName);
    }

    private function mockAuthManager(MockInterface $userProvider, string $guard): void
    {
        $guardMock = $this->mock(Guard::class);
        $guardMock->shouldReceive('getProvider')->andReturn($userProvider);
        $guardMock->shouldReceive('getRecallerName')->andReturn(self::COOKIE_RECALLER_NAME);

        $authManagerMock = $this->mock(AuthManager::class);
        $authManagerMock->shouldReceive('guard')->with($guard)->andReturn($guardMock);

        app()->instance('auth', $authManagerMock);
    }

    /*
     * Asserts.
     */

    private function assertCookieValue(SetCookie $cookie, string $expectedCookieValue): void
    {
        $encrypter = resolve('encrypter');

        $expectedCookiePrefix = CookieValuePrefix::create(self::COOKIE_RECALLER_NAME, $encrypter->getKey());

        $actualValueWithPrefix = $encrypter->decrypt($cookie->getValue(), unserialize: false);
        $this->assertStringStartsWith(
            $expectedCookiePrefix,
            $actualValueWithPrefix,
            'The (decrypted) cookie value doesn\'t have the cookie prefix.',
        );

        $actualValue = str_replace($expectedCookiePrefix, '', $actualValueWithPrefix);
        $this->assertEquals(
            $expectedCookieValue,
            $actualValue,
        );
    }
}
