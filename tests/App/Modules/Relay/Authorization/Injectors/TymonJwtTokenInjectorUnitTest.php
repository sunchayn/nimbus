<?php

namespace Sunchayn\Nimbus\Tests\App\Modules\Relay\Authorization\Injectors;

use Illuminate\Container\Container;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Contracts\Auth\Guard;
use Illuminate\Http\Client\PendingRequest;
use Mockery;
use PHPUnit\Framework\Attributes\CoversClass;
use Sunchayn\Nimbus\Modules\Config\ActiveApplicationResolver;
use Sunchayn\Nimbus\Modules\Config\Exceptions\MisconfiguredValueException;
use Sunchayn\Nimbus\Modules\Relay\Authorization\Injectors\TymonJwtTokenInjector;
use Sunchayn\Nimbus\Tests\TestCase;

#[CoversClass(TymonJwtTokenInjector::class)]
class TymonJwtTokenInjectorUnitTest extends TestCase
{
    private ActiveApplicationResolver|MockInterface $projectManagerMock;

    private Container $containerMock;

    protected function setUp(): void
    {
        parent::setUp();

        $this->projectManagerMock = Mockery::mock(ActiveApplicationResolver::class);
        $this->containerMock = Mockery::mock(Container::class);
    }

    public function test_it_throws_exception_when_tymon_jwt_auth_not_installed(): void
    {
        // Arrange

        if (class_exists(\Tymon\JWTAuth\JWTGuard::class)) {
            $this->markTestSkipped('Tymon JWT Auth is installed, cannot test missing dependency scenario');
        }

        // Anticipate

        $this->expectException(MisconfiguredValueException::class);
        $this->expectExceptionMessage('The config value for `nimbus.auth.special.injector` is an injector that requires the following dependency <tymon/jwt-auth>');

        // Act

        new TymonJwtTokenInjector(
            activeApplicationResolver: $this->projectManagerMock,
            container: $this->containerMock,
        );
    }

    public function test_it_generates_jwt_token_and_attaches_to_request(): void
    {
        // Arrange

        $authenticatable = Mockery::mock(Authenticatable::class);

        $authenticatable
            ->shouldReceive('getAuthIdentifier')
            ->andReturn(123);

        $guardMock = Mockery::mock(Guard::class);

        $pendingRequestMock = Mockery::mock(PendingRequest::class);

        $injector = $this->instantiateInjector($guardMock);

        // Anticipate

        $guardMock
            ->shouldReceive('login')
            ->with($authenticatable)
            ->once()
            ->andReturn('eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9.test.token');

        $pendingRequestMock
            ->shouldReceive('withToken')
            ->once()
            ->withArgs(function (string $token) {
                $this->assertEquals('eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9.test.token', $token);

                return true;
            })
            ->andReturnSelf();

        // Act

        $result = $injector->attach($pendingRequestMock, $authenticatable);

        // Assert

        $this->assertSame($pendingRequestMock, $result);
    }

    /*
     * Helpers.
     */

    private function instantiateInjector(
        Guard $guardMock
    ): Mockery\MockInterface&TymonJwtTokenInjector {
        $mock = $this->mock(TymonJwtTokenInjector::class)->shouldAllowMockingProtectedMethods()->makePartial();

        $mock->shouldReceive('getGuard')->andReturn($guardMock);

        return $mock;
    }
}
