<?php

namespace Sunchayn\Nimbus\Tests\App\Modules\Relay\Services\Authorization\Handlers;

use Closure;
use Generator;
use Illuminate\Support\Arr;
use InvalidArgumentException;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\CoversMethod;
use PHPUnit\Framework\Attributes\DataProvider;
use Sunchayn\Nimbus\Modules\Relay\Services\Authorization\DataTransferObjects\AuthorizationCredentials;
use Sunchayn\Nimbus\Modules\Relay\Services\Authorization\Enums\AuthorizationTypeEnum;
use Sunchayn\Nimbus\Modules\Relay\Services\Authorization\Handlers\AuthorizationHandlerFactory;
use Sunchayn\Nimbus\Modules\Relay\Services\Authorization\Handlers\BasicAuthAuthorizationHandler;
use Sunchayn\Nimbus\Modules\Relay\Services\Authorization\Handlers\BearerAuthorizationHandler;
use Sunchayn\Nimbus\Modules\Relay\Services\Authorization\Handlers\CurrentUserAuthorizationHandler;
use Sunchayn\Nimbus\Modules\Relay\Services\Authorization\Handlers\ImpersonateUserAuthorizationHandler;
use Sunchayn\Nimbus\Modules\Relay\Services\Authorization\Handlers\NoAuthorizationHandler;
use Sunchayn\Nimbus\Tests\TestCase;

#[CoversClass(AuthorizationHandlerFactory::class)]
#[CoversMethod(BasicAuthAuthorizationHandler::class, 'fromArray')]
class AuthorizationHandlerFactoryUnitTest extends TestCase
{
    #[DataProvider('createAuthorizationHandlerDataProvider')]
    public function test_it_creates_handler(
        AuthorizationCredentials $authorizationCredentials,
        string $expectedAuthorizationHandler,
        Closure $checkIsValid,
    ): void {
        // Arrange

        $factory = resolve(AuthorizationHandlerFactory::class);

        // Act

        $actualAuthorizationHandler = $factory->create($authorizationCredentials);

        // Assert

        $this->assertInstanceOf(
            $expectedAuthorizationHandler,
            $actualAuthorizationHandler,
        );

        $this->assertTrue(
            $checkIsValid->call($this, $authorizationCredentials, $actualAuthorizationHandler),
        );
    }

    public static function createAuthorizationHandlerDataProvider(): Generator
    {
        yield 'None handler' => [
            'authorizationCredentials' => AuthorizationCredentials::none(),
            'expectedAuthorizationHandler' => NoAuthorizationHandler::class,
            'checkIsValid' => fn (AuthorizationCredentials $credentials, NoAuthorizationHandler $handler): bool => true,
        ];

        yield 'Bearer handler' => [
            'authorizationCredentials' => new AuthorizationCredentials(AuthorizationTypeEnum::Bearer, value: 'foobar'),
            'expectedAuthorizationHandler' => BearerAuthorizationHandler::class,
            'checkIsValid' => fn (AuthorizationCredentials $credentials, BearerAuthorizationHandler $handler): bool => $handler->token === 'foobar',
        ];

        yield 'Basic handler' => [
            'authorizationCredentials' => new AuthorizationCredentials(AuthorizationTypeEnum::Basic, value: ['username' => 'foo', 'password' => 'bar']),
            'expectedAuthorizationHandler' => BasicAuthAuthorizationHandler::class,
            'checkIsValid' => fn (AuthorizationCredentials $credentials, BasicAuthAuthorizationHandler $handler): bool => $handler->username === 'foo' && $handler->password === 'bar',
        ];

        yield 'Impersonate handler' => [
            'authorizationCredentials' => new AuthorizationCredentials(AuthorizationTypeEnum::Impersonate, value: 22),
            'expectedAuthorizationHandler' => ImpersonateUserAuthorizationHandler::class,
            'checkIsValid' => fn (AuthorizationCredentials $credentials, ImpersonateUserAuthorizationHandler $handler): bool => $handler->userId === 22,
        ];

        yield 'Current User handler' => [
            'authorizationCredentials' => new AuthorizationCredentials(AuthorizationTypeEnum::CurrentUser, value: 22),
            'expectedAuthorizationHandler' => CurrentUserAuthorizationHandler::class,
            'checkIsValid' => fn (AuthorizationCredentials $credentials, CurrentUserAuthorizationHandler $handler): bool => true,
        ];
    }

    #[DataProvider('authorizationHandlerValidationDataProvider')]
    public function test_it_validates_values_upon_creation(
        AuthorizationCredentials $authorizationCredentials,
        string $expectedError,
    ): void {
        // Arrange

        $factory = resolve(AuthorizationHandlerFactory::class);

        // Anticipate

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage($expectedError);

        // Act

        $factory->create($authorizationCredentials);
    }

    public static function authorizationHandlerValidationDataProvider(): Generator
    {
        yield 'Impersonate handler doesnt allow `nulls`' => [
            'authorizationCredentials' => new AuthorizationCredentials(AuthorizationTypeEnum::Impersonate, value: null),
            'expectedError' => 'Impersonate user ID cannot be null.',
        ];

        yield 'Impersonate handler doesnt allow strings`' => [
            'authorizationCredentials' => new AuthorizationCredentials(AuthorizationTypeEnum::Impersonate, value: 'abc'),
            'expectedError' => 'Impersonate user ID must be an integer.',
        ];

        yield 'Impersonate handler doesnt allow arrays`' => [
            'authorizationCredentials' => new AuthorizationCredentials(AuthorizationTypeEnum::Impersonate, value: ['username' => 'foo', 'password' => 'bar']),
            'expectedError' => 'Impersonate user ID must be an integer.',
        ];

        yield 'Bearer handler doesnt allow `nulls`' => [
            'authorizationCredentials' => new AuthorizationCredentials(AuthorizationTypeEnum::Bearer, value: null),
            'expectedError' => 'Bearer token must be a string',
        ];

        yield 'Bearer handler doesnt allow arrays' => [
            'authorizationCredentials' => new AuthorizationCredentials(AuthorizationTypeEnum::Bearer, value: ['username' => 'foo', 'password' => 'bar']),
            'expectedError' => 'Bearer token must be a string',
        ];

        yield 'Basic handler doesnt allow `nulls`' => [
            'authorizationCredentials' => new AuthorizationCredentials(AuthorizationTypeEnum::Basic, value: null),
            'expectedError' => 'Basic auth credentials must be an array',
        ];

        yield 'Basic handler doesnt allow strings' => [
            'authorizationCredentials' => new AuthorizationCredentials(
                AuthorizationTypeEnum::Basic,
                value: Arr::random(['abc', 22]) // <- 22 implicitly converted to "22"
            ),
            'expectedError' => 'Basic auth credentials must be an array',
        ];
    }
}
