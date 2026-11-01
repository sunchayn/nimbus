<?php

declare(strict_types=1);

namespace Sunchayn\Nimbus\Http\Api\Routes;

use Illuminate\Contracts\Config\Repository;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Sunchayn\Nimbus\Modules\Routes\Actions\ExtractRoutesAction;
use Sunchayn\Nimbus\Modules\Routes\Exceptions\RoutesProcessingException;
use Sunchayn\Nimbus\Modules\Routes\Services\Snapshot\ManifestManager;

class RoutesController
{
    public function __construct(
        private readonly ExtractRoutesAction $extractRoutesAction,
        private readonly Repository $repository,
        private readonly ManifestManager $manifestManager,
    ) {}

    public function __invoke(Request $request): JsonResponse
    {
        $isSnapshotEnabled = (bool) $this->repository->get('nimbus.snapshot', false);

        if ($isSnapshotEnabled && $request->boolean('refresh')) {
            $this->manifestManager->clear();
        }

        try {
            $routes = $this->extractRoutesAction->execute();
        } catch (RoutesProcessingException $routesProcessingException) {
            return response()->json(
                [$routesProcessingException->getFrontEndIdentifier() => $routesProcessingException->toArray()],
                status: 500,
            );
        }

        return response()->json(
            $routes->toFrontendArray(
                shouldNestFolders: (bool) $this->repository->get('nimbus.nested_folders', false),
            ),
            headers: $isSnapshotEnabled
                ? array_filter(['X-Nimbus-Snapshot-At' => (string) $this->manifestManager->createdAt()])
                : [],
        );
    }
}
