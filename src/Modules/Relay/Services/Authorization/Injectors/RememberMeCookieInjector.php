<?php

namespace Sunchayn\Nimbus\Modules\Relay\Services\Authorization\Injectors;

use Illuminate\Container\Container;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Contracts\Auth\Guard;
use Illuminate\Contracts\Auth\StatefulGuard;
use Illuminate\Contracts\Auth\UserProvider;
use Illuminate\Cookie\CookieValuePrefix;
use Illuminate\Encryption\Encrypter;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\NotFoundExceptionInterface;
use Sunchayn\Nimbus\Modules\Config\ActiveApplicationResolver;
use Sunchayn\Nimbus\Modules\Config\Exceptions\MisconfiguredValueException;
use Sunchayn\Nimbus\Modules\Relay\Services\Authorization\Contracts\SpecialAuthenticationInjectorContract;

class RememberMeCookieInjector implements SpecialAuthenticationInjectorContract
{
    private UserProvider $userProvider;

    private Guard $authGuard;

    private Encrypter $encrypter;

    /**
     * @throws NotFoundExceptionInterface
     * @throws ContainerExceptionInterface
     * @throws MisconfiguredValueException
     */
    public function __construct(
        private readonly Request $relayRequest,
        private readonly Container $container,
        ActiveApplicationResolver $activeApplicationResolver,
    ) {
        $this->encrypter = $this->container->get('encrypter');

        $this->authGuard = $this->container->get('auth')->guard(
            $activeApplicationResolver->getAuthGuard(),
        );

        if (! $this->authGuard instanceof StatefulGuard) {
            throw MisconfiguredValueException::becauseOfInvalidGuardInjectorCombination('Please use a stateful guard.');
        }

        if (! method_exists($this->authGuard, 'getProvider')) {
            throw MisconfiguredValueException::becauseOfInvalidGuardInjectorCombination('Please use a guard that exposes a provider.');
        }

        $this->userProvider = $this->authGuard->getProvider();
    }

    public function attach(
        PendingRequest $pendingRequest,
        Authenticatable $authenticatable,
    ): PendingRequest {
        if ($this->forwardRememberMeCookie($pendingRequest, $authenticatable)) {
            return $pendingRequest;
        }

        // Generate authentication artifacts for the user.
        $recallerToken = $this->generateRecallerTokenFor($authenticatable);

        return $pendingRequest->withCookies(
            [
                $this->authGuard->getRecallerName() => $recallerToken,
            ],
            // Note: This implementation assumes same-domain requests. Cross-domain
            // impersonation would require additional configuration for cookie domains.
            $this->relayRequest->getHost(),
        );
    }

    private function forwardRememberMeCookie(PendingRequest $pendingRequest, Authenticatable $authenticatable): bool
    {
        if ($authenticatable->getAuthIdentifier() !== $this->relayRequest->user()?->getAuthIdentifier()) {
            return false;
        }

        $recallerCookieKey = $this->authGuard->getRecallerName();
        $recallerCookieToForward = $this->relayRequest->cookies->get($recallerCookieKey);

        if (! $recallerCookieToForward) {
            return false;
        }

        $pendingRequest->withCookies(
            [
                $recallerCookieKey => $recallerCookieToForward,
            ],
            domain: $this->relayRequest->getHost(),
        );

        return true;
    }

    /**
     * Generate authentication artifacts for the user.
     *
     * Creates a recaller cookie that will authenticate the target user
     * when the request reaches the target application. No session cookie
     * is needed since we're not forwarding the original session.
     */
    private function generateRecallerTokenFor(Authenticatable $authenticatable): string
    {
        // Generate recaller token for "remember me" functionality
        $recallerToken = $authenticatable->getAuthIdentifier().'|'.$this->getRememberMeTokenFor($authenticatable).'|'.$authenticatable->getAuthPasswordName();

        return $this->encrypter->encrypt(
            CookieValuePrefix::create($this->authGuard->getRecallerName(), $this->encrypter->getKey()).$recallerToken,
            false,
        );
    }

    private function getRememberMeTokenFor(Authenticatable $authenticatable): string
    {
        $existentToken = $authenticatable->getRememberToken();

        if (filled($existentToken)) {
            return $existentToken;
        }

        $newToken = hash('sha256', Str::random(60));

        $this->userProvider->updateRememberToken($authenticatable, $newToken);

        return $newToken;
    }
}
