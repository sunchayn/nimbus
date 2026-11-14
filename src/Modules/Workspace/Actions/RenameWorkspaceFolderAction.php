<?php

declare(strict_types=1);

namespace Sunchayn\Nimbus\Modules\Workspace\Actions;

use Illuminate\Support\Facades\File;
use Sunchayn\Nimbus\Modules\Workspace\DataTransferObjects\RenameFolderData;
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
    public function execute(RenameFolderData $data): FolderPath
    {
        if ($data->folder->isRoot()) {
            throw WorkspaceException::invalidFolder();
        }

        if ($data->name->depth() !== 1) {
            throw WorkspaceException::invalidFolder();
        }

        return $this->lockWorkspaceAction->execute(
            fn (): FolderPath => $this->actuallyRename($data),
        );
    }

    private function actuallyRename(RenameFolderData $data): FolderPath
    {
        $source = $this->findWorkspaceFolderDirectoryAction->execute($data->folder);
        $parentDirectory = dirname($source);
        $destination = "{$parentDirectory}/{$data->name}";

        if ($destination === $source) {
            return $data->folder;
        }

        if (File::exists($destination)) {
            throw WorkspaceException::folderExists((string) $data->name);
        }

        $workspace = $this->scanWorkspaceAction->execute();

        $this->moveDirectory(
            $source,
            $destination,
        );

        $newFolder = $data->folder->parent()->join($data->name->name());

        $newManifestFile = $workspace->manifest->withFolderRenamed(
            from: $data->folder,
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
