<?php

declare(strict_types=1);

namespace Sunchayn\Nimbus\Modules\Magic\Toolbox\Actions;

use Illuminate\Contracts\Hashing\Hasher;

/** @final */
class HashValueAction
{
    public function __construct(private readonly Hasher $hasher) {}

    /**
     * Checks the value against a hash when one is given, and makes a hash otherwise.
     *
     * @return array{hash: string}|array{matches: bool, needs_rehash: bool}
     */
    public function execute(string $value, ?string $hash = null): array
    {
        if ($hash === null || $hash === '') {
            return ['hash' => $this->hasher->make($value)];
        }

        $matches = $this->hasher->check($value, $hash);

        return ['matches' => $matches, 'needs_rehash' => $matches && $this->hasher->needsRehash($hash)];
    }
}
