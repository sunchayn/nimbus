<?php

namespace Sunchayn\Nimbus\Tests\App\Modules\Relay\Authorization\Handlers;

use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Http\Request;
use Mockery\MockInterface;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\CoversMethod;
use PHPUnit\Framework\Attributes\TestWith;
use Sunchayn\Nimbus\Modules\Relay\Authorization\Exceptions\InvalidAuthorizationValueException;
use Sunchayn\Nimbus\Modules\Relay\Authorization\Handlers\ImpersonateUserAuthorizationHandler;
use Sunchayn\Nimbus\Tests\App\Modules\Relay\Authorization\Handlers\Shared\HandlesRecallerCookies;
use Sunchayn\Nimbus\Tests\App\Modules\Relay\Authorization\Handlers\Stubs\DummyAuthenticatable;
use Sunchayn\Nimbus\Tests\App\Modules\Relay\Authorization\Handlers\Stubs\DummySpecialAuthenticationInjector;
use Sunchayn\Nimbus\Tests\TestCase;

#[CoversClass(ImpersonateUserAuthorizationHandler::class)]
#[CoversMethod(InvalidAuthorizationValueException::class, 'becauseUserIsNotFound')]
class ImpersonateUserAuthorizationHandlerFunctionalTest extends TestCase
{
    use HandlesRecallerCookies;

    public function test_it_authorizes_requests(): void
    {
        // Arrange

        $this->mock(\Sunchayn\Nimbus\Modules\Config\ActiveApplicationResolver::class, function (MockInterface $mock) use (&$guardName) {
            $mock->shouldReceive('getAuthGuard')->andReturn($guardName = fake()->word());
            $mock->shouldReceive('getSpecialAuthInjector')->andReturn(DummySpecialAuthenticationInjector::class);
        });
        $dummyAuthenticatable = new DummyAuthenticatable(id: $userId = fake()->randomNumber());

        $this->mockAuthManagerToUseDummyModel($userId, $dummyAuthenticatable, $guardName);

        $relayRequest = Request::create('ping');
        $relayRequest->headers->set('HOST', $relayRequestHost = fake()->domainName());

        $dummySpecialAuthenticationInjectorMock = $this->mock(DummySpecialAuthenticationInjector::class);

        $handler = resolve(
            ImpersonateUserAuthorizationHandler::class,
            [
                'userId' => $userId,
                'relayRequest' => $relayRequest,
            ],
        );

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

    #[TestWith([0], 'ID Equals to Zero')]
    #[TestWith([-1], 'Negative ID')]
    public function test_it_breaks_with_invalid_user_id(int $coefficient): void
    {
        // Arrange

        $invalidUserId = fake()->randomNumber() * $coefficient;

        // Anticipate

        $this->expectException(InvalidAuthorizationValueException::class);

        $this->expectExceptionMessage('User ID didn\'t resolve to a user to impersonate.');

        $this->expectExceptionCode(InvalidAuthorizationValueException::USER_IS_NOT_FOUND);

        // Act

        resolve(ImpersonateUserAuthorizationHandler::class, ['userId' => $invalidUserId]);
    }

    public function test_it_breaks_when_user_is_not_found(): void
    {
        // Arrange

        $nonExistentUserId = fake()->randomNumber() + 1;

        $this->mockAuthManagerToUseDummyModel($nonExistentUserId, authenticatable: null, guardName: 'web');

        $pendingRequest = resolve(PendingRequest::class);

        $handler = resolve(ImpersonateUserAuthorizationHandler::class, ['userId' => $nonExistentUserId]);

        // Anticipate

        $this->expectException(InvalidAuthorizationValueException::class);

        $this->expectExceptionMessage('User ID didn\'t resolve to a user to impersonate.');

        $this->expectExceptionCode(InvalidAuthorizationValueException::USER_IS_NOT_FOUND);

        // Act

        $handler->authorize($pendingRequest);
    }
}
