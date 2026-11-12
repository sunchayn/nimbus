<?php

declare(strict_types=1);

namespace Sunchayn\Nimbus\Modules\Workspace\Actions;

use Illuminate\Support\Facades\File;
use Sunchayn\Nimbus\Modules\Workspace\DataTransferObjects\WriteWorkspaceFileData;
use Sunchayn\Nimbus\Modules\Workspace\Exceptions\WorkspaceException;
use Sunchayn\Nimbus\Modules\Workspace\ValueObjects\FolderPath;

/**
 * Deletes a folder and everything inside it.
 *
 * @final
 */
class DeleteWorkspaceFolderAction
{
    public function __construct(
        private readonly FindWorkspaceFolderDirectoryAction $findWorkspaceFolderDirectoryAction,
        private readonly LockWorkspaceAction $lockWorkspaceAction,
        private readonly ScanWorkspaceAction $scanWorkspaceAction,
        private readonly WriteWorkspaceFileAction $writeWorkspaceFileAction,
    ) {}

    /**
     * @throws WorkspaceException
     */
    public function execute(FolderPath $folder): void
    {
        if ($folder->isRoot()) {
            throw WorkspaceException::invalidFolder();
        }

        $this->lockWorkspaceAction->execute(
            fn () => $this->actuallyDelete($folder),
        );
    }

    private function actuallyDelete(FolderPath $folder): void
    {
        $dirpath = $this->findWorkspaceFolderDirectoryAction->execute($folder);

        $workspace = $this->scanWorkspaceAction->execute();

        $this->deleteDirectory($dirpath);

        $newManifestFile = $workspace->manifest->removeFolder($folder);

        $this->writeWorkspaceFileAction->execute(
            data: WriteWorkspaceFileData::fromManifest($newManifestFile),
        );
    }

    private function deleteDirectory(string $dirpath): void
    {
        File::deleteDirectory($dirpath);

        if (File::missing($dirpath)) {
            return;
        }

        throw WorkspaceException::writeFailed();
    }
}
