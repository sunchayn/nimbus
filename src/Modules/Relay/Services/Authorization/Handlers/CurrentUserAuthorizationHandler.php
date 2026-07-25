<?php

namespace Sunchayn\Nimbus\Modules\Relay\Services\Authorization\Handlers;

use Illuminate\Container\Container;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Contracts\Auth\UserProvider;
use Illuminate\Contracts\Config\Repository as ConfigRepository;
use Illuminate\Contracts\Encryption\DecryptException;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Http\Request;
use Illuminate\Session\SessionManager;
use Illuminate\Session\Store;
use Illuminate\Support\Arr;
use Sunchayn\Nimbus\Modules\Config\ActiveApplicationResolver;
use Sunchayn\Nimbus\Modules\Relay\Services\Authorization\Concerns\UsesSpecialAuthenticationInjector;
use Sunchayn\Nimbus\Modules\Relay\Services\Authorization\Exceptions\InvalidAuthorizationValueException;

/**
 * Authorization handler that forwards the current user's session cookies.
 *
 * This handler ensures the outbound request is authenticated as the current
 * Laravel user. It supports two modes:
 *  - Directly from the current authenticated user on the relay request.
 *  - Fallback to session cookie resolution when no user is bound to the request.
 */
class CurrentUserAuthorizationHandler implements AuthorizationHandler
{
    use UsesSpecialAuthenticationInjector;

    private readonly UserProvider $userProvider;

    public function __construct(
        private readonly Request $relayRequest,
        private readonly Container $container,
        private readonly ActiveApplicationResolver $projectManager,
        private readonly ConfigRepository $configRepository,
    ) {
        $this->userProvider = $this->resolveUserProvider();
    }

    public function authorize(PendingRequest $pendingRequest): PendingRequest
    {
        $user = $this->getAuthenticatedUser();

        if (! $user instanceof \Illuminate\Contracts\Auth\Authenticatable) {
            return $pendingRequest;
        }

        return $this
            ->getInjector($this->container, $this->projectManager)
            ->attach($pendingRequest, $user);
    }

    /**
     * Resolve the active user provider for the configured authentication guard.
     */
    private function resolveUserProvider(): UserProvider
    {
        $authManager = $this->container->get('auth');

        return $authManager->guard($this->projectManager->getAuthGuard())->getProvider();
    }

    /**
     * Determine the currently authenticated user either from the relay request
     * or, if missing, by resolving from a valid Laravel session cookie.
     */
    private function getAuthenticatedUser(): ?Authenticatable
    {
        return $this->relayRequest->user() ?? $this->getUserFromSession();
    }

    /**
     * Attempt to retrieve the authenticated user from the Laravel session cookie.
     *
     * @throws InvalidAuthorizationValueException
     */
    private function getUserFromSession(): ?Authenticatable
    {
        $sessionCookieName = $this->configRepository->get('session.cookie');

        $sessionCookie = $this->relayRequest->cookies->get($sessionCookieName);
        if (! is_string($sessionCookie)) {
            return null;
        }

        /** @var Store $session */
        $session = $this->container->make(SessionManager::class)->driver();

        try {
            $sessionId = $this->extractSessionIdFromCookie($sessionCookie);
        } catch (DecryptException) {
            throw InvalidAuthorizationValueException::becauseCookieIsNotDecryptable();
        }

        $session->setId(id: $sessionId);
        $session->start();

        $tokenPrefix = 'login_'.($this->projectManager->getAuthGuard());

        $userId = Arr::first(
            $session->all(),
            fn (mixed $value, string $key): bool => str_starts_with($key, $tokenPrefix),
        );

        return $this->userProvider->retrieveById($userId);
    }

    /**
     * Decrypt the Laravel session cookie and extract the session ID.
     *
     * Laravel’s session cookie is formatted as "payload|signature", where the
     * payload contains the encrypted session identifier.
     *
     * @throws \Illuminate\Contracts\Encryption\DecryptException
     */
    private function extractSessionIdFromCookie(string $cookieValue): string
    {
        $encrypter = $this->container->make('encrypter');
        $decrypted = $encrypter->decrypt($cookieValue, unserialize: false);

        [, $sessionId] = explode('|', $decrypted, 2);

        return $sessionId;
    }
}
