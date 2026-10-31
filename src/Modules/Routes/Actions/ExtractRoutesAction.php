<?php

declare(strict_types=1);

namespace Sunchayn\Nimbus\Modules\Routes\Actions;

use Illuminate\Contracts\Config\Repository;
use LogicException;
use Sunchayn\Nimbus\Modules\Routes\Collections\ExtractedRoutesCollection;
use Sunchayn\Nimbus\Modules\Routes\Exceptions\RoutesProcessingException;
use Sunchayn\Nimbus\Modules\Routes\Services\RoutesProcessors\Strategies\RoutesProcessorContract;
use Sunchayn\Nimbus\Modules\Routes\Services\Snapshot\ManifestManager;

class ExtractRoutesAction
{
    public function __construct(
        private readonly RoutesProcessorContract $routesProcessorContract,
        private readonly ManifestManager $manifestManager,
        private readonly Repository $repository,
    ) {}

    /**
     * @throws RoutesProcessingException
     */
    public function execute(): ExtractedRoutesCollection
    {
        if (! $this->repository->get('nimbus.snapshot', false)) {
            return $this->processRoutes();
        }

        try {
            if (! $this->manifestManager->isStale()) {
                return $this->manifestManager->getExtractedRoutes();
            }
        } catch (LogicException) {
            // Manifest is broken. Do nothing, it will be rebuilt below.
        }

        $this->manifestManager->reset();

        $routes = $this->processRoutes();

        $this->manifestManager->setExtractedRoutes($routes);
        $this->manifestManager->save();

        return $routes;
    }

    /**
     * @throws RoutesProcessingException
     */
    public function processRoutes(): ExtractedRoutesCollection
    {
        return $this->routesProcessorContract->process();
    }
}
