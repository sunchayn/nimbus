<?php

namespace Sunchayn\Nimbus\Modules\Routes\Actions;

use Sunchayn\Nimbus\Modules\Routes\Services\IgnoredRoutesService;
use Sunchayn\Nimbus\Modules\Routes\Services\Snapshot\ManifestManager;

/**
 * Adda the specified route to the ignore list.
 *
 * @example
 * Input:  "api/users|GET,POST"
 * Output: Redirects to clean URL after adding route to ignored list
 */
class IgnoreRouteErrorAction
{
    public function __construct(
        private readonly IgnoredRoutesService $ignoredRoutesService,
        private readonly ManifestManager $manifestManager,
    ) {}

    public function execute(string $ignoreData): void
    {
        // Parse the ignore data (format: "uri|methods")
        $parts = explode('|', $ignoreData);

        if (count($parts) !== 2) {
            return;
        }

        if (empty($parts[0])) {
            return;
        }

        $uri = $parts[0];
        $methods = json_decode($parts[1], true) ?: [];

        $this->ignoredRoutesService->add($uri, $methods);

        // The ignored routes are shared by all applications, so every snapshot is outdated.
        $this->manifestManager->clearAll();
    }
}
