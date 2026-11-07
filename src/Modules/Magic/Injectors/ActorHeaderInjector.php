<?php

namespace Sunchayn\Nimbus\Modules\Magic\Injectors;

use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Http\Client\PendingRequest;
use Sunchayn\Nimbus\Modules\Config\ActiveApplicationResolver;
use Sunchayn\Nimbus\Modules\Magic\ValueObjects\NimbusHeaders;
use Sunchayn\Nimbus\Modules\Relay\Services\Authorization\Contracts\SpecialAuthenticationInjectorContract;

/**
 * Authenticates the relayed request through the `X-Nimbus-Actor` header.
 *
 * The header is read by `ActorHeaderListener` in the target application.
 */
class ActorHeaderInjector implements SpecialAuthenticationInjectorContract
{
    private ?string $guard = null;

    public function __construct(
        private readonly ActiveApplicationResolver $activeApplicationResolver,
    ) {}

    public function forGuard(string $guard): self
    {
        $this->guard = $guard;

        return $this;
    }

    public function attach(
        PendingRequest $pendingRequest,
        Authenticatable $authenticatable,
    ): PendingRequest {
        return $pendingRequest->withHeaders([
            NimbusHeaders::ACTOR => NimbusHeaders::actorValue(
                $this->guard ?? $this->activeApplicationResolver->getAuthGuard(),
                (string) $authenticatable->getAuthIdentifier(),
            ),
        ]);
    }
}
