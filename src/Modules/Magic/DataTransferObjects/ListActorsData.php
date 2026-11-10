<?php

namespace Sunchayn\Nimbus\Modules\Magic\DataTransferObjects;

use Sunchayn\Nimbus\Http\Api\Actors\ListActorsRequest;
use Sunchayn\Nimbus\Modules\Config\ActiveApplicationResolver;

class ListActorsData
{
    public function __construct(
        public readonly string $guard,
        public readonly string $search = '',
    ) {}

    public static function fromRequest(ListActorsRequest $request, ActiveApplicationResolver $resolver): self
    {
        $guard = $request->input('guard');
        $search = $request->input('q');

        return new self(
            guard: is_string($guard) && $guard !== '' ? $guard : $resolver->getAuthGuard(),
            search: is_string($search) ? trim($search) : '',
        );
    }
}
