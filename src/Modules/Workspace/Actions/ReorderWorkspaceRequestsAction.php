<?php

declare(strict_types=1);

namespace Sunchayn\Nimbus\Modules\Workspace\Actions;

use Sunchayn\Nimbus\Modules\Workspace\DataTransferObjects\ManifestFile;
use Sunchayn\Nimbus\Modules\Workspace\DataTransferObjects\WriteWorkspaceFileData;
use Sunchayn\Nimbus\Modules\Workspace\Exceptions\WorkspaceException;
use Sunchayn\Nimbus\Modules\Workspace\ValueObjects\FolderPath;

/** @final */
class ReorderWorkspaceRequestsAction
{
    public function __construct(
        private readonly LockWorkspaceAction $lockWorkspaceAction,
        private readonly ScanWorkspaceAction $scanWorkspaceAction,
        private readonly WriteWorkspaceFileAction $writeWorkspaceFileAction,
    ) {}

    /**
     * @param  list<string>  $ids
     *
     * @throws WorkspaceException
     */
    public function execute(
        FolderPath $folder,
        array $ids,
    ): void {
        $this->lockWorkspaceAction->execute(
            fn () => $this->actuallyReorder(
                folder: $folder,
                ids: $ids,
            ),
        );
    }

    /**
     * @param  list<string>  $ids
     */
    private function actuallyReorder(
        FolderPath $folder,
        array $ids,
    ): void {
        $workspace = $this->scanWorkspaceAction->execute();

        if (! $workspace->entries->hasFolder($folder)) {
            throw WorkspaceException::folderNotFound((string) $folder);
        }

        $entries = $workspace
            ->entries
            ->withRequestsOrdered(
                folder: $folder,
                orderedList: $ids,
            )
            ->flattenedByHierarchy();

        $newManifestFile = ManifestFile::fromEntries($entries);

        $this->writeWorkspaceFileAction->execute(
            data: WriteWorkspaceFileData::fromManifest($newManifestFile),
        );
    }
}
