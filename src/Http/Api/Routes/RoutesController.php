<?php

declare(strict_types=1);

namespace Sunchayn\Nimbus\Http\Api\Routes;

use Illuminate\Contracts\Config\Repository;
use Illuminate\Http\JsonResponse;
use Sunchayn\Nimbus\Modules\Routes\Exceptions\RoutesProcessingException;
use Sunchayn\Nimbus\Modules\Routes\Services\RoutesProcessors\Strategies\RoutesProcessorContract;

class RoutesController
{
    public function __construct(
        private readonly RoutesProcessorContract $routesProcessorContract,
        private readonly Repository $repository,
    ) {}

    public function __invoke(): JsonResponse
    {
        try {
            $routes = $this->routesProcessorContract->process();
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
        );
    }
}
