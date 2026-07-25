<?php

namespace Sunchayn\Nimbus\Tests\App\Modules\Relay\Services\Authorization\Handlers;

use Illuminate\Http\Client\PendingRequest;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\CoversMethod;
use PHPUnit\Framework\Attributes\TestWith;
use Sunchayn\Nimbus\Modules\Relay\Services\Authorization\Exceptions\InvalidAuthorizationValueException;
use Sunchayn\Nimbus\Modules\Relay\Services\Authorization\Handlers\BearerAuthorizationHandler;
use Sunchayn\Nimbus\Tests\TestCase;

#[CoversClass(BearerAuthorizationHandler::class)]
#[CoversMethod(InvalidAuthorizationValueException::class, 'becauseBearerTokenValueIsNotString')]
class BearerAuthorizationHandlerFunctionalTest extends TestCase
{
    public function test_it_authorizes_requests(): void
    {
        // Arrange

        $pendingRequest = resolve(PendingRequest::class);

        $token = fake()->sha256();

        $handler = new BearerAuthorizationHandler("$token "); // <- Adding a space to assert trimming.

        // Act

        $pendingRequestResponse = $handler->authorize($pendingRequest);

        // Assert

        $this->assertEquals(
            "Bearer $token",
            $pendingRequestResponse->getOptions()['headers']['Authorization'] ?? '--not-found--',
        );

        $this->assertSame($pendingRequest, $pendingRequestResponse);
    }

    #[TestWith([''])]
    #[TestWith(['    '])]
    public function test_it_breaks_with_invalid_token(string $invalidToken): void
    {
        // Anticipate

        $this->expectException(InvalidAuthorizationValueException::class);

        $this->expectExceptionMessage('Bearer token value is not a string.');

        $this->expectExceptionCode(InvalidAuthorizationValueException::BEARER_TOKEN_IS_NOT_STRING);

        // Act

        resolve(BearerAuthorizationHandler::class, ['token' => $invalidToken]);
    }
}
