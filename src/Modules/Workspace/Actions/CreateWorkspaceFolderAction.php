<?php

declare(strict_types=1);

namespace Sunchayn\Nimbus\Modules\Workspace\Actions;

use Sunchayn\Nimbus\Modules\Workspace\DataTransferObjects\WriteWorkspaceFileData;
use Sunchayn\Nimbus\Modules\Workspace\Exceptions\WorkspaceException;
use Sunchayn\Nimbus\Modules\Workspace\ValueObjects\FolderPath;
use Sunchayn\Nimbus\Modules\Workspace\ValueObjects\WorkspaceConfig;

/**
 * Creates a folder and the folders above it.
 *
 * @final
 */
class CreateWorkspaceFolderAction
{
    public function __construct(
        private readonly WorkspaceConfig $config,
        private readonly LockWorkspaceAction $lockWorkspaceAction,
        private readonly ScanWorkspaceAction $scanWorkspaceAction,
        private readonly WriteWorkspaceFileAction $writeWorkspaceFileAction,
    ) {}

    /**
     * @throws WorkspaceException
     */
    public function execute(FolderPath $folder): FolderPath
    {
        if ($folder->isRoot()) {
            throw WorkspaceException::invalidFolder();
        }

        return $this->lockWorkspaceAction->execute(
            fn (): FolderPath => $this->actuallyCreate($folder),
        );
    }

    private function actuallyCreate(FolderPath $folder): FolderPath
    {
        $manifest = $this->scanWorkspaceAction->execute()->manifest;

        $this->createDirectory($folder);

        $newManifestFile = $manifest->addFolder($folder);

        $this->writeWorkspaceFileAction->execute(
            data: WriteWorkspaceFileData::fromManifest($newManifestFile),
        );

        return $folder;
    }

    private function createDirectory(FolderPath $folder): void
    {
        $dirpath = $this->config->folderDirectory($folder);

        if (is_dir($dirpath)) {
            return; // Already created.
        }

        @mkdir($dirpath, 0755, true);

        if (is_dir($dirpath)) {
            return;
        }

        throw WorkspaceException::writeFailed();
    }
}
