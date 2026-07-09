<?php

namespace Sunchayn\Nimbus\Http\Api\Collections;

use Illuminate\Http\JsonResponse;
use Sunchayn\Nimbus\Modules\Collections\Contracts\CollectionStoreContract;
use Sunchayn\Nimbus\Modules\Collections\DataTransferObjects\CollectionData;

/**
 * Reads and syncs the team's shared environment collections.
 *
 * The frontend loads collections on boot (index) and pushes its full list back
 * whenever they change (sync). Persistence is delegated to the configured
 * CollectionStoreContract driver (git-tracked JSON files by default).
 */
class NimbusCollectionsController
{
    public function index(CollectionStoreContract $collectionStoreContract): JsonResponse
    {
        return $this->respondWith($collectionStoreContract->all());
    }

    public function sync(
        SyncCollectionsRequest $syncCollectionsRequest,
        CollectionStoreContract $collectionStoreContract,
    ): JsonResponse {
        $collectionStoreContract->sync($syncCollectionsRequest->collections());

        return $this->respondWith($collectionStoreContract->all());
    }

    /**
     * @param  array<int, CollectionData>  $collections
     */
    private function respondWith(array $collections): JsonResponse
    {
        return new JsonResponse([
            'collections' => array_map(
                fn (CollectionData $collectionData): array => $collectionData->toFrontendArray(),
                $collections,
            ),
        ]);
    }
}
