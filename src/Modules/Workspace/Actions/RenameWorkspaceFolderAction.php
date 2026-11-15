<?php

declare(strict_types=1);

namespace Sunchayn\Nimbus\Modules\Workspace\Actions;

use Illuminate\Support\Facades\File;
use Sunchayn\Nimbus\Modules\Workspace\DataTransferObjects\WriteWorkspaceFileData;
use Sunchayn\Nimbus\Modules\Workspace\Exceptions\WorkspaceException;
use Sunchayn\Nimbus\Modules\Workspace\ValueObjects\FolderPath;

/**
 * Renames a folder without moving it.
 *
 * @final
 */
class RenameWorkspaceFolderAction
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
    public function execute(
        FolderPath $folder,
        FolderPath $newName,
    ): FolderPath {
        if ($folder->isRoot()) {
            throw WorkspaceException::invalidFolder();
        }

        if ($newName->depth() !== 1) {
            throw WorkspaceException::invalidFolder();
        }

        return $this->lockWorkspaceAction->execute(
            fn (): FolderPath => $this->actuallyRename(
                folder: $folder,
                newName: $newName,
            ),
        );
    }

    private function actuallyRename(
        FolderPath $folder,
        FolderPath $newName,
    ): FolderPath {
        $source = $this->findWorkspaceFolderDirectoryAction->execute($folder);
        $parentDirectory = dirname($source);
        $destination = "{$parentDirectory}/{$newName}";

        if ($destination === $source) {
            return $folder;
        }

        if (File::exists($destination)) {
            throw WorkspaceException::folderExists((string) $newName);
        }

        $workspace = $this->scanWorkspaceAction->execute();

        $this->moveDirectory(
            $source,
            $destination,
        );

        $newFolder = $folder->parent()->join($newName->name());

        $newManifestFile = $workspace->manifest->withFolderRenamed(
            from: $folder,
            to: $newFolder,
        );

        $this->writeWorkspaceFileAction->execute(
            data: WriteWorkspaceFileData::fromManifest($newManifestFile),
        );

        return $newFolder;
    }

    private function moveDirectory(string $source, string $target): void
    {
        $moved = @rename($source, $target);

        if ($moved) {
            return;
        }

        throw WorkspaceException::writeFailed();
    }
}
