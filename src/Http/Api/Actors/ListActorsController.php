<?php

namespace Sunchayn\Nimbus\Http\Api\Actors;

use Illuminate\Http\JsonResponse;
use Sunchayn\Nimbus\Modules\Config\ActiveApplicationResolver;
use Sunchayn\Nimbus\Modules\Magic\Actions\ListActorsAction;
use Sunchayn\Nimbus\Modules\Magic\DataTransferObjects\ActorData;
use Sunchayn\Nimbus\Modules\Magic\DataTransferObjects\ListActorsData;

class ListActorsController
{
    public function __invoke(
        ListActorsRequest $request,
        ListActorsAction $listActorsAction,
        ActiveApplicationResolver $resolver,
    ): JsonResponse {
        $listActorsData = ListActorsData::fromRequest($request, $resolver);

        return response()->json([
            'guard' => $listActorsData->guard,
            'guards' => array_keys((array) config('auth.guards', [])),
            'actors' => array_map(
                fn (ActorData $actor): array => $actor->toArray(),
                $listActorsAction->execute(data: $listActorsData),
            ),
        ]);
    }
}
