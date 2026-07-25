<?php

namespace Sunchayn\Nimbus\Tests\App\Modules\Relay\Services\Authorization\Handlers;

use Illuminate\Http\Client\PendingRequest;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\CoversMethod;
use PHPUnit\Framework\Attributes\TestWith;
use Sunchayn\Nimbus\Modules\Relay\Services\Authorization\Exceptions\InvalidAuthorizationValueException;
use Sunchayn\Nimbus\Modules\Relay\Services\Authorization\Handlers\BasicAuthAuthorizationHandler;
use Sunchayn\Nimbus\Tests\TestCase;

#[CoversClass(BasicAuthAuthorizationHandler::class)]
#[CoversMethod(InvalidAuthorizationValueException::class, 'becauseBasicAuthCredentialsAreInvalid')]
class BasicAuthAuthorizationHandlerFunctionalTest extends TestCase
{
    public function test_it_authorizes_requests(): void
    {
        // Arrange

        $pendingRequest = resolve(PendingRequest::class);

        $handler = new BasicAuthAuthorizationHandler(
            $username = fake()->userName(),
            $password = fake()->password(),
        );

        // Act

        $pendingRequestResponse = $handler->authorize($pendingRequest);

        // Assert

        $this->assertEquals(
            [$username, $password],
            $pendingRequestResponse->getOptions()['auth'] ?? [],
        );

        $this->assertSame($pendingRequest, $pendingRequestResponse);
    }

    #[TestWith(['username' => '', 'password' => ''])]
    #[TestWith(['username' => '', 'password' => '   '])]
    #[TestWith(['username' => '   ', 'password' => '   '])]
    public function test_it_breaks_with_invalid_credentials(string $username, string $password): void
    {
        // Anticipate

        $this->expectException(InvalidAuthorizationValueException::class);

        $this->expectExceptionMessage('Basic Auth credentials are invalid. Expects array{username: string, password: string}.');

        $this->expectExceptionCode(InvalidAuthorizationValueException::BASIC_AUTH_SHAPE_IS_INVALID);

        // Act

        resolve(BasicAuthAuthorizationHandler::class, ['username' => $username, 'password' => $password]);
    }

    #[TestWith(['credentials' => ['username' => 'foobar']])]
    #[TestWith(['credentials' => ['password' => 'foobar']])]
    public function test_it_breaks_with_invalid_credentials_for_from_array(array $credentials): void
    {
        // Anticipate

        $this->expectException(InvalidAuthorizationValueException::class);

        $this->expectExceptionMessage('Basic Auth credentials are invalid. Expects array{username: string, password: string}.');

        $this->expectExceptionCode(InvalidAuthorizationValueException::BASIC_AUTH_SHAPE_IS_INVALID);

        // Act

        BasicAuthAuthorizationHandler::fromArray($credentials);
    }
}
