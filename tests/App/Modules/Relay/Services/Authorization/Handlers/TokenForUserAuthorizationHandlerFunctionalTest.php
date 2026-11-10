<?php

namespace Sunchayn\Nimbus\Tests\App\Modules\Relay\Services\Authorization\Handlers;

use Illuminate\Http\Client\PendingRequest;
use Mockery\MockInterface;
use PHPUnit\Framework\Attributes\CoversClass;
use Sunchayn\Nimbus\Modules\Config\ActiveApplicationResolver;
use Sunchayn\Nimbus\Modules\Relay\Services\Authorization\Exceptions\InvalidAuthorizationValueException;
use Sunchayn\Nimbus\Modules\Relay\Services\Authorization\Handlers\TokenForUserAuthorizationHandler;
use Sunchayn\Nimbus\Tests\App\Modules\Relay\Services\Authorization\Handlers\Shared\HandlesRecallerCookies;
use Sunchayn\Nimbus\Tests\App\Modules\Relay\Services\Authorization\Handlers\Stubs\DummyAuthenticatable;
use Sunchayn\Nimbus\Tests\TestCase;

#[CoversClass(TokenForUserAuthorizationHandler::class)]
class TokenForUserAuthorizationHandlerFunctionalTest extends TestCase
{
    use HandlesRecallerCookies;

    public function test_it_sends_a_sanctum_shaped_token_with_the_requested_abilities(): void
    {
        // Arrange

        $user = $this->makeSanctumUser(7, $this->makeStoredToken());

        $handler = $this->makeHandler($user, abilities: ['posts:read', 'posts:write']);

        // Act

        $pendingRequest = $handler->authorize(resolve(PendingRequest::class));

        // Assert

        $this->assertSame('Bearer 1|plain', $pendingRequest->getOptions()['headers']['Authorization'] ?? null);
        $this->assertSame(['posts:read', 'posts:write'], $user->receivedAbilities);
    }

    public function test_it_finds_the_user_through_the_requested_guard(): void
    {
        // Arrange

        $user = $this->makeSanctumUser(7, $this->makeStoredToken());

        $handler = $this->makeHandler($user, abilities: [], guard: 'tokens');

        // Act

        $pendingRequest = $handler->authorize(resolve(PendingRequest::class));

        // Assert

        $this->assertSame('Bearer 1|plain', $pendingRequest->getOptions()['headers']['Authorization'] ?? null);
    }

    public function test_it_deletes_the_token_only_once_after_the_response(): void
    {
        // Arrange

        $storedToken = $this->makeStoredToken();

        $handler = $this->makeHandler($this->makeSanctumUser(7, $storedToken), abilities: []);

        $handler->authorize(resolve(PendingRequest::class));

        // Act

        $handler->revoke();
        $handler->revoke();

        // Assert

        $this->assertSame(1, $storedToken->deleted);
    }

    public function test_it_supports_a_passport_shaped_token_and_defaults_to_all_abilities(): void
    {
        // Arrange

        $storedToken = $this->makeStoredToken();

        $user = new class(8, $storedToken) extends DummyAuthenticatable
        {
            public ?array $receivedAbilities = null;

            public function __construct(int $id, private readonly object $storedToken)
            {
                parent::__construct($id);
            }

            public function createToken(string $name, array $scopes): object
            {
                $this->receivedAbilities = $scopes;

                return (object) ['accessToken' => 'passport-plain', 'token' => $this->storedToken];
            }
        };

        $handler = $this->makeHandler($user, abilities: []);

        // Act

        $pendingRequest = $handler->authorize(resolve(PendingRequest::class));
        $handler->revoke();

        // Assert

        $this->assertSame('Bearer passport-plain', $pendingRequest->getOptions()['headers']['Authorization'] ?? null);
        $this->assertSame(['*'], $user->receivedAbilities);
        $this->assertSame(1, $storedToken->deleted);
    }

    public function test_it_breaks_when_the_user_cannot_create_tokens(): void
    {
        // Arrange

        $handler = $this->makeHandler(new DummyAuthenticatable(id: 9), abilities: []);

        // Anticipate

        $this->expectException(InvalidAuthorizationValueException::class);
        $this->expectExceptionCode(InvalidAuthorizationValueException::USER_CANNOT_HAVE_TOKENS);

        // Act

        $handler->authorize(resolve(PendingRequest::class));
    }

    public function test_it_breaks_when_the_user_is_not_found(): void
    {
        // Arrange

        $this->mock(ActiveApplicationResolver::class, function (MockInterface $mock): void {
            $mock->shouldReceive('getAuthGuard')->andReturn('web');
        });

        $this->mockAuthManagerToUseDummyModel(5, authenticatable: null, guardName: 'web');

        $handler = resolve(TokenForUserAuthorizationHandler::class, ['userId' => 5, 'abilities' => []]);

        // Anticipate

        $this->expectException(InvalidAuthorizationValueException::class);
        $this->expectExceptionCode(InvalidAuthorizationValueException::USER_IS_NOT_FOUND);

        // Act

        $handler->authorize(resolve(PendingRequest::class));
    }

    private function makeStoredToken(): object
    {
        return new class
        {
            public int $deleted = 0;

            public function delete(): void
            {
                $this->deleted++;
            }
        };
    }

    private function makeSanctumUser(int $id, object $storedToken): DummyAuthenticatable
    {
        return new class($id, $storedToken) extends DummyAuthenticatable
        {
            public ?array $receivedAbilities = null;

            public function __construct(int $id, private readonly object $storedToken)
            {
                parent::__construct($id);
            }

            public function createToken(string $name, array $abilities): object
            {
                $this->receivedAbilities = $abilities;

                return (object) ['plainTextToken' => '1|plain', 'accessToken' => $this->storedToken];
            }
        };
    }

    /**
     * @param  list<string>  $abilities
     */
    private function makeHandler(DummyAuthenticatable $user, array $abilities, ?string $guard = null): TokenForUserAuthorizationHandler
    {
        $this->mock(ActiveApplicationResolver::class, function (MockInterface $mock): void {
            $mock->shouldReceive('getAuthGuard')->andReturn('web');
        });

        $this->mockAuthManagerToUseDummyModel($user->getAuthIdentifier(), $user, $guard ?? 'web');

        return resolve(TokenForUserAuthorizationHandler::class, [
            'userId' => $user->getAuthIdentifier(),
            'abilities' => $abilities,
            'guard' => $guard,
        ]);
    }
}
