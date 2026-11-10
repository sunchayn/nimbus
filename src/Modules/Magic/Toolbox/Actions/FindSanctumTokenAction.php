<?php

declare(strict_types=1);

namespace Sunchayn\Nimbus\Modules\Magic\Toolbox\Actions;

use Sunchayn\Nimbus\Modules\Magic\Toolbox\Exceptions\InvalidToolInputException;

/** @final */
class FindSanctumTokenAction
{
    private const TOKEN_MODEL = 'Laravel\Sanctum\PersonalAccessToken';

    /**
     * @return array<string, mixed>
     *
     * @throws InvalidToolInputException
     */
    public function execute(string $plainTextToken): array
    {
        if (! class_exists(self::TOKEN_MODEL)) {
            throw InvalidToolInputException::becauseSanctumIsMissing();
        }

        /** @var callable $finder */
        $finder = [self::TOKEN_MODEL, 'findToken'];

        $token = $finder(trim($plainTextToken));

        if ($token === null) {
            throw InvalidToolInputException::becauseTokenIsUnknown();
        }

        return [
            'id' => $token->getKey(),
            'name' => $token->name,
            'abilities' => $token->abilities,
            'tokenable_type' => $token->tokenable_type,
            'tokenable_id' => $token->tokenable_id,
            'last_used_at' => $token->last_used_at?->toIso8601String(),
            'expires_at' => $token->expires_at?->toIso8601String(),
        ];
    }
}
