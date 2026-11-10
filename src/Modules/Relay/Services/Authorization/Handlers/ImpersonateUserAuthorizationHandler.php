<?php

namespace Sunchayn\Nimbus\Modules\Relay\Services\Authorization\Handlers;

use Illuminate\Container\Container;
use Illuminate\Contracts\Auth\UserProvider;
use Illuminate\Contracts\Container\BindingResolutionException;
use Illuminate\Http\Client\PendingRequest;
use Sunchayn\Nimbus\Modules\Config\ActiveApplicationResolver;
use Sunchayn\Nimbus\Modules\Config\Exceptions\MisconfiguredValueException;
use Sunchayn\Nimbus\Modules\Magic\Injectors\ActorHeaderInjector;
use Sunchayn\Nimbus\Modules\Relay\Services\Authorization\Concerns\UsesSpecialAuthenticationInjector;
use Sunchayn\Nimbus\Modules\Relay\Services\Authorization\Exceptions\InvalidAuthorizationValueException;

class ImpersonateUserAuthorizationHandler implements AuthorizationHandler
{
    use UsesSpecialAuthenticationInjector;

    private UserProvider $userProvider;

    public function __construct(
        public readonly int|string $userId,
        private readonly Container $container,
        private readonly ActiveApplicationResolver $projectManager,
        private readonly ?string $guard = null,
    ) {
        if ($userId === '' || (is_int($userId) && $userId <= 0)) {
            throw InvalidAuthorizationValueException::becauseUserIsNotFound();
        }

        $this->userProvider = $this
            ->container->get('auth')
            ->guard(name: $this->guard ?? $this->projectManager->getAuthGuard())
            ->getProvider();
    }

    /**
     * @throws BindingResolutionException
     * @throws MisconfiguredValueException
     */
    public function authorize(PendingRequest $pendingRequest): PendingRequest
    {
        $user = $this->userProvider->retrieveById($this->userId);

        if ($user === null) {
            throw InvalidAuthorizationValueException::becauseUserIsNotFound();
        }

        $specialAuthenticationInjectorContract = $this->getInjector($this->container, $this->projectManager);

        // Only the actor header can act on a guard other than the configured one.
        if ($specialAuthenticationInjectorContract instanceof ActorHeaderInjector && $this->guard !== null) {
            $specialAuthenticationInjectorContract->forGuard($this->guard);
        }

        return $specialAuthenticationInjectorContract->attach($pendingRequest, $user);
    }
}
