<?php

namespace Sunchayn\Nimbus\Modules\Magic\ValueObjects;

use Illuminate\Http\Request;

/**
 * Reads and validates the Nimbus control headers of a request in one place.
 */
readonly class NimbusHeaders
{
    public const TRANSACTION_MODE = 'X-Nimbus-Transaction-Mode';

    public const ACTOR = 'X-Nimbus-Actor';

    public function __construct(
        public bool $transactionMode = false,
        public ?string $actorGuard = null,
        public ?string $actorKey = null,
    ) {}

    public static function fromRequest(Request $request): self
    {
        [$guard, $key] = self::parseActor($request->header(self::ACTOR));

        return new self(
            transactionMode: $request->header(self::TRANSACTION_MODE) === '1',
            actorGuard: $guard,
            actorKey: $key,
        );
    }

    public static function actorValue(string $guard, string $key): string
    {
        return $guard.':'.$key;
    }

    public function hasActor(): bool
    {
        return $this->actorGuard !== null && $this->actorKey !== null;
    }

    /**
     * The value is `guard:key`, split on the first colon so keys may contain colons.
     *
     * @return array{0: ?string, 1: ?string}
     */
    private static function parseActor(mixed $value): array
    {
        if (! is_string($value) || ! str_contains($value, ':')) {
            return [null, null];
        }

        [$guard, $key] = explode(':', $value, 2);

        if ($guard === '' || $key === '') {
            return [null, null];
        }

        return [$guard, $key];
    }
}
