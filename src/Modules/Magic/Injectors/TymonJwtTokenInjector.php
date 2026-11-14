<?php

namespace Sunchayn\Nimbus\Modules\Magic\Injectors;

use Illuminate\Container\Container;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Contracts\Auth\Guard;
use Illuminate\Http\Client\PendingRequest;
use Sunchayn\Nimbus\Modules\Config\ActiveApplicationResolver;
use Sunchayn\Nimbus\Modules\Config\Exceptions\MisconfiguredValueException;
use Sunchayn\Nimbus\Modules\Relay\Services\Authorization\Contracts\SpecialAuthenticationInjectorContract;
use Tymon\JWTAuth\JWTGuard;

class TymonJwtTokenInjector implements SpecialAuthenticationInjectorContract
{
    private Guard $guard;

    /**
     * @throws MisconfiguredValueException
     */
    public function __construct(
        private readonly ActiveApplicationResolver $activeApplicationResolver,
        private readonly Container $container,
    ) {
        if (! class_exists(JWTGuard::class)) {
            throw MisconfiguredValueException::becauseOfMissingDependency(dependency: 'tymon/jwt-auth');
        }

        $this->guard = $this
            ->container->make('auth')
            ->guard(name: $this->activeApplicationResolver->getAuthGuard());

        if (! $this->guard instanceof JWTGuard) {
            throw MisconfiguredValueException::becauseOfInvalidGuardInjectorCombination("Please use a `\Tymon\JWTAuth\JWTGuard` guard.");
        }
    }

    protected function getGuard(): Guard
    {
        return $this->guard;
    }

    public function attach(
        PendingRequest $pendingRequest,
        Authenticatable $authenticatable,
    ): PendingRequest {
        /** @var string $bearerToken */
        // @phpstan-ignore-next-line The `JWTGuard` will indeed return the token here even though the contract annotates it as void.
        $bearerToken = $this->getGuard()->login($authenticatable);

        $pendingRequest->withToken(
            token: $bearerToken,
        );

        return $pendingRequest;
    }
}
