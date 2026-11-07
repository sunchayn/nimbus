<?php

namespace Sunchayn\Nimbus\Tests\App\Modules\Magic\Injectors;

use Illuminate\Auth\GenericUser;
use Illuminate\Http\Client\PendingRequest;
use Mockery;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Sunchayn\Nimbus\Modules\Config\ActiveApplicationResolver;
use Sunchayn\Nimbus\Modules\Magic\Injectors\ActorHeaderInjector;

#[CoversClass(ActorHeaderInjector::class)]
class ActorHeaderInjectorUnitTest extends TestCase
{
    public function test_it_attaches_the_actor_header_with_the_guard_and_the_user_key(): void
    {
        // Arrange

        $resolver = Mockery::mock(ActiveApplicationResolver::class);
        $resolver->shouldReceive('getAuthGuard')->andReturn('sanctum');

        $pendingRequest = Mockery::mock(PendingRequest::class);
        $pendingRequest->shouldReceive('withHeaders')
            ->once()
            ->with(['X-Nimbus-Actor' => 'sanctum:01HZX3Y8K2M4N6P8Q0R2S4T6V8'])
            ->andReturnSelf();

        // Act

        $result = (new ActorHeaderInjector($resolver))->attach(
            $pendingRequest,
            new GenericUser(['id' => '01HZX3Y8K2M4N6P8Q0R2S4T6V8']),
        );

        // Assert

        $this->assertSame($pendingRequest, $result);
    }

    public function test_it_uses_the_guard_chosen_for_the_request(): void
    {
        // Arrange

        $resolver = Mockery::mock(ActiveApplicationResolver::class);
        $resolver->shouldReceive('getAuthGuard')->andReturn('web');

        $pendingRequest = Mockery::mock(PendingRequest::class);
        $pendingRequest->shouldReceive('withHeaders')
            ->once()
            ->with(['X-Nimbus-Actor' => 'api:5'])
            ->andReturnSelf();

        // Act & Assert

        $result = (new ActorHeaderInjector($resolver))
            ->forGuard('api')
            ->attach($pendingRequest, new GenericUser(['id' => 5]));

        $this->assertSame($pendingRequest, $result);
    }
}
