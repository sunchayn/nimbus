<?php

declare(strict_types=1);

namespace Sunchayn\Nimbus\Modules\Workspace\DataTransferObjects;

use Sunchayn\Nimbus\Modules\Workspace\Collections\WorkspaceEntriesCollection;

/**
 * The result of scanning the workspace.
 */
class WorkspaceScanResult
{
    /**
     * @param  WorkspaceEntriesCollection  $entries  Every folder and request file, in the order they are shown.
     */
    public function __construct(
        public readonly ManifestFile $manifest,
        public readonly WorkspaceEntriesCollection $entries,
    ) {}
}
