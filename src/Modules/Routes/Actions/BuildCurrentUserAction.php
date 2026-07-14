<?php

declare(strict_types=1);

namespace Sunchayn\Nimbus\Modules\Routes\Actions;

use Illuminate\Contracts\Auth\Factory;

class BuildCurrentUserAction
{
    public function __construct(
        private readonly Factory $factory,
    ) {}

    /**
     * @return array{id: int}|null
     */
    public function execute(): ?array
    {
        if ($this->factory->guard()->user() === null) {
            return null;
        }

        return ['id' => $this->factory->guard()->user()->getAuthIdentifier()];
    }
}
