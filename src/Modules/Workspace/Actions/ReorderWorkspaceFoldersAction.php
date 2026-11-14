<?php

declare(strict_types=1);

namespace Sunchayn\Nimbus\Modules\Workspace\Actions;

use Sunchayn\Nimbus\Modules\Workspace\DataTransferObjects\ManifestFile;
use Sunchayn\Nimbus\Modules\Workspace\DataTransferObjects\WriteWorkspaceFileData;
use Sunchayn\Nimbus\Modules\Workspace\Exceptions\WorkspaceException;
use Sunchayn\Nimbus\Modules\Workspace\ValueObjects\FolderPath;

/** @final */
class ReorderWorkspaceFoldersAction
{
    public function __construct(
        private readonly LockWorkspaceAction $lockWorkspaceAction,
        private readonly ScanWorkspaceAction $scanWorkspaceAction,
        private readonly WriteWorkspaceFileAction $writeWorkspaceFileAction,
    ) {}

    /**
     * @param  list<FolderPath>  $folders  Folder paths in the order they should be listed.
     *
     * @throws WorkspaceException
     */
    public function execute(array $folders): void
    {
        $this->lockWorkspaceAction->execute(
            fn () => $this->actuallyReorder($folders),
        );
    }

    /**
     * @param  list<FolderPath>  $folders
     */
    private function actuallyReorder(array $folders): void
    {
        $workspace = $this->scanWorkspaceAction->execute();

        $entries = $workspace
            ->entries
            ->withFoldersOrdered($folders)
            ->flattenedByHierarchy();

        $newManifestFile = ManifestFile::fromEntries($entries);

        $this->writeWorkspaceFileAction->execute(
            data: WriteWorkspaceFileData::fromManifest($newManifestFile),
        );
    }
}
