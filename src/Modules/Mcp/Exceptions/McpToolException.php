<?php

declare(strict_types=1);

namespace Sunchayn\Nimbus\Modules\Mcp\Exceptions;

use Exception;

/**
 * An expected failure of a tool call. It reaches the agent as `error.code`, `error.message`, and `error.hint`.
 */
class McpToolException extends Exception
{
    public const ROUTE_NOT_FOUND = 'ROUTE_NOT_FOUND';

    public const ROUTE_AMBIGUOUS = 'ROUTE_AMBIGUOUS';

    public const ROUTES_UNAVAILABLE = 'ROUTES_UNAVAILABLE';

    public const INVALID_INPUT = 'INVALID_INPUT';

    public const DECRYPTION_FAILED = 'DECRYPTION_FAILED';

    public const ACTOR_NOT_FOUND = 'ACTOR_NOT_FOUND';

    public const ENVIRONMENT_NOT_ALLOWED = 'ENVIRONMENT_NOT_ALLOWED';

    public const RATE_LIMITED = 'RATE_LIMITED';

    public const REQUEST_FAILED = 'REQUEST_FAILED';

    public const SENT_REQUEST_NOT_FOUND = 'SENT_REQUEST_NOT_FOUND';

    private function __construct(
        public readonly string $errorCode,
        string $message,
        public readonly string $hint,
    ) {
        parent::__construct($message);
    }

    /**
     * @param  array<int, string>  $suggestions
     */
    public static function becauseRouteIsNotFound(string $reference, array $suggestions): self
    {
        return new self(
            self::ROUTE_NOT_FOUND,
            sprintf('No route matches `%s`.', $reference),
            $suggestions === []
                ? 'Call nimbus_list_routes to find a route id.'
                : 'Did you mean '.implode(', ', array_map(fn (string $id): string => '`'.$id.'`', $suggestions)).'?',
        );
    }

    /**
     * @param  array<int, string>  $applications
     */
    public static function becauseRouteIsAmbiguous(string $reference, array $applications): self
    {
        $quotedApplications = implode(', ', array_map(static fn (string $application): string => '`'.$application.'`', $applications));

        return new self(
            self::ROUTE_AMBIGUOUS,
            sprintf('`%s` matches routes in several applications, %s.', $reference, $quotedApplications),
            'Pass `application` with one of them.',
        );
    }

    /**
     * @param  array<int, string>  $known
     */
    public static function becauseApplicationIsUnknown(string $application, array $known): self
    {
        return new self(
            self::INVALID_INPUT,
            sprintf('The application `%s` is not configured.', $application),
            'Call nimbus_list_applications. The keys are '.implode(', ', array_map(static fn (string $key): string => '`'.$key.'`', $known)).'.',
        );
    }

    public static function becauseRoutesAreUnavailable(string $reason): self
    {
        return new self(self::ROUTES_UNAVAILABLE, $reason, 'Fix the routes error in the Nimbus UI or the config, then try again.');
    }

    public static function becauseInputIsInvalid(string $message, string $hint): self
    {
        return new self(self::INVALID_INPUT, $message, $hint);
    }

    public static function becauseDecryptionFailed(): self
    {
        return new self(
            self::DECRYPTION_FAILED,
            'The value could not be decrypted.',
            'Check that the value was encrypted by this application and that the kind is correct.',
        );
    }

    public static function becauseActorIsNotFound(string $guard, string $actor): self
    {
        return new self(
            self::ACTOR_NOT_FOUND,
            sprintf('No user with the key `%s` exists for the `%s` guard.', $actor, $guard),
            'Use the primary key of an existing user.',
        );
    }

    public static function becauseEnvironmentIsNotAllowed(string $message, string $hint): self
    {
        return new self(self::ENVIRONMENT_NOT_ALLOWED, $message, $hint);
    }

    public static function becauseRateIsLimited(int $seconds): self
    {
        return new self(
            self::RATE_LIMITED,
            'Too many send requests in the last minute.',
            sprintf('Wait %d seconds, or raise `nimbus.mcp.rate_limit`.', $seconds),
        );
    }

    public static function becauseSentRequestIsNotFound(string $sentRequestId): self
    {
        return new self(
            self::SENT_REQUEST_NOT_FOUND,
            sprintf('No sent request has the id `%s`.', $sentRequestId),
            'Only the latest requests are kept. Send the request again with nimbus_send_request.',
        );
    }

    public static function becauseRequestFailed(string $reason): self
    {
        return new self(self::REQUEST_FAILED, $reason, 'Check that the application boots and that the driver in `nimbus.mcp.driver` can reach it.');
    }

    /**
     * @return array{error: array{code: string, message: string, hint: string}}
     */
    public function toArray(): array
    {
        return ['error' => ['code' => $this->errorCode, 'message' => $this->getMessage(), 'hint' => $this->hint]];
    }
}
