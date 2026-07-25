<?php

namespace Sunchayn\Nimbus\Tests\App\Modules\Relay\Services\Authorization\Handlers;

use Illuminate\Http\Client\PendingRequest;
use Mockery\MockInterface;
use PHPUnit\Framework\Attributes\CoversClass;
use Sunchayn\Nimbus\Modules\Relay\Services\Authorization\Handlers\NoAuthorizationHandler;
use Sunchayn\Nimbus\Tests\TestCase;

#[CoversClass(NoAuthorizationHandler::class)]
class NoAuthAuthorizationHandlerFunctionalTest extends TestCase
{
    public function test_it_does_nothing(): void
    {
        // Arrange

        /**
         * Creating a Mock in place of a Spy here so any method call breaks. We expect nothing to be called.
         *
         * @var PendingRequest&MockInterface $pendingRequestMock
         */
        $pendingRequestMock = $this->mock(PendingRequest::class);

        $handler = resolve(NoAuthorizationHandler::class);

        // Act

        $pendingRequestResponse = $handler->authorize($pendingRequestMock);

        // Assert

        $this->assertSame($pendingRequestMock, $pendingRequestResponse);
    }
}
