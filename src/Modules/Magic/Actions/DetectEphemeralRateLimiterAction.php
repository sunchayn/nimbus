<?php

declare(strict_types=1);

namespace Sunchayn\Nimbus\Modules\Magic\Actions;

use Illuminate\Contracts\Config\Repository;

/** @final */
class DetectEphemeralRateLimiterAction
{
    private const EPHEMERAL_DRIVERS = ['array', 'null'];

    public function __construct(private readonly Repository $repository) {}

    /**
     * Tells whether the rate limiter forgets its hits between requests.
     * The limiter uses the `cache.limiter` store when one is set, and the default store otherwise.
     */
    public function execute(): bool
    {
        $store = $this->repository->get('cache.limiter') ?: $this->repository->get('cache.default');

        return in_array($this->repository->get(sprintf('cache.stores.%s.driver', $store)), self::EPHEMERAL_DRIVERS, true);
    }
}
