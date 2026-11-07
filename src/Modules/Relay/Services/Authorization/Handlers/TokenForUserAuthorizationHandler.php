<?php

namespace Sunchayn\Nimbus\Modules\Relay\Services\Authorization\Handlers;

use Closure;
use Illuminate\Container\Container;
use Illuminate\Contracts\Auth\UserProvider;
use Illuminate\Http\Client\PendingRequest;
use Sunchayn\Nimbus\Modules\Config\ActiveApplicationResolver;
use Sunchayn\Nimbus\Modules\Relay\Services\Authorization\Exceptions\InvalidAuthorizationValueException;

/**
 * Creates a personal access token for a user, sends it as a bearer token, and deletes it after the response.
 *
 * Sanctum and Passport are supported through the `createToken` method of the user model.
 * Neither package is required, so their result objects are read without referencing their classes.
 */
class TokenForUserAuthorizationHandler implements AuthorizationHandler, RevokesAfterResponse
{
    public const TOKEN_NAME = 'nimbus';

    private readonly UserProvider $userProvider;

    private ?Closure $revoker = null;

    /**
     * @param  list<string>  $abilities
     */
    public function __construct(
        public readonly int|string $userId,
        public readonly array $abilities,
        Container $container,
        ActiveApplicationResolver $projectManager,
        ?string $guard = null,
    ) {
        if ($userId === '' || (is_int($userId) && $userId <= 0)) {
            throw InvalidAuthorizationValueException::becauseUserIsNotFound();
        }

        $this->userProvider = $container->get('auth')
            ->guard(name: $guard ?? $projectManager->getAuthGuard())
            ->getProvider();
    }

    public function authorize(PendingRequest $pendingRequest): PendingRequest
    {
        $user = $this->userProvider->retrieveById($this->userId);

        if ($user === null) {
            throw InvalidAuthorizationValueException::becauseUserIsNotFound();
        }

        if (! method_exists($user, 'createToken')) {
            throw InvalidAuthorizationValueException::becauseUserCannotHaveTokens();
        }

        $result = $user->createToken(self::TOKEN_NAME, $this->abilities === [] ? ['*'] : $this->abilities);

        // Sanctum returns `plainTextToken` and `accessToken` (a model). Passport returns `accessToken` (a string) and `token` (a model).
        $sanctumToken = data_get($result, 'plainTextToken');
        $plainTextToken = is_string($sanctumToken) ? $sanctumToken : data_get($result, 'accessToken');

        if (! is_string($plainTextToken) || $plainTextToken === '') {
            throw InvalidAuthorizationValueException::becauseUserCannotHaveTokens();
        }

        $model = is_string($sanctumToken) ? data_get($result, 'accessToken') : data_get($result, 'token');

        $this->revoker = fn () => is_object($model) && method_exists($model, 'delete') ? $model->delete() : null;

        return $pendingRequest->withToken($plainTextToken);
    }

    public function revoke(): void
    {
        if ($this->revoker instanceof Closure) {
            ($this->revoker)();

            $this->revoker = null;
        }
    }
}
