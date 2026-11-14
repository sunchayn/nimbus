<?php

declare(strict_types=1);

namespace Sunchayn\Nimbus\Modules\Workspace\Actions;

use Sunchayn\Nimbus\Modules\Workspace\DataTransferObjects\ManifestFile;
use Sunchayn\Nimbus\Modules\Workspace\DataTransferObjects\ReorderRequestsData;
use Sunchayn\Nimbus\Modules\Workspace\DataTransferObjects\WriteWorkspaceFileData;
use Sunchayn\Nimbus\Modules\Workspace\Exceptions\WorkspaceException;

/** @final */
class ReorderWorkspaceRequestsAction
{
    public function __construct(
        private readonly LockWorkspaceAction $lockWorkspaceAction,
        private readonly ScanWorkspaceAction $scanWorkspaceAction,
        private readonly WriteWorkspaceFileAction $writeWorkspaceFileAction,
    ) {}

    /**
     * @throws WorkspaceException
     */
    public function execute(ReorderRequestsData $data): void
    {
        $this->lockWorkspaceAction->execute(
            fn () => $this->actuallyReorder($data),
        );
    }

    private function actuallyReorder(ReorderRequestsData $data): void
    {
        $workspace = $this->scanWorkspaceAction->execute();

        if (! $workspace->entries->hasFolder($data->folder)) {
            throw WorkspaceException::folderNotFound((string) $data->folder);
        }

        $entries = $workspace
            ->entries
            ->withRequestsOrdered(
                folder: $data->folder,
                orderedList: $data->ids,
            )
            ->flattenedByHierarchy();

        $newManifestFile = ManifestFile::fromEntries($entries);

        $this->writeWorkspaceFileAction->execute(
            data: WriteWorkspaceFileData::fromManifest($newManifestFile),
        );
    }
}
