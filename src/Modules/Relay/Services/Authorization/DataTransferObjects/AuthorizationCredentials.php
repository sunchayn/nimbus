<?php

namespace Sunchayn\Nimbus\Modules\Relay\Services\Authorization\DataTransferObjects;

use Sunchayn\Nimbus\Modules\Relay\Services\Authorization\Enums\AuthorizationTypeEnum;

readonly class AuthorizationCredentials
{
    /**
     * @param  string|array{username: string, password: string}|null  $value
     * @param  list<string>  $abilities
     */
    public function __construct(
        public AuthorizationTypeEnum $type,
        public string|array|null $value,
        public ?string $guard = null,
        public array $abilities = [],
    ) {}

    public static function none(): self
    {
        return new self(
            type: AuthorizationTypeEnum::None,
            value: null,
        );
    }
}
