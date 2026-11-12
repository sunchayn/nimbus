<?php

declare(strict_types=1);

namespace Sunchayn\Nimbus\Modules\Workspace\Actions;

use Illuminate\Support\Facades\File;
use Sunchayn\Nimbus\Modules\Workspace\DataTransferObjects\WriteWorkspaceFileData;
use Sunchayn\Nimbus\Modules\Workspace\Exceptions\WorkspaceException;

/** @final */
class DeleteWorkspaceRequestAction
{
    public function __construct(
        private readonly LockWorkspaceAction $lockWorkspaceAction,
        private readonly ScanWorkspaceAction $scanWorkspaceAction,
        private readonly WriteWorkspaceFileAction $writeWorkspaceFileAction,
    ) {}

    /**
     * @throws WorkspaceException
     */
    public function execute(string $id): void
    {
        $this->lockWorkspaceAction->execute(
            fn () => $this->actuallyDelete($id),
        );
    }

    private function actuallyDelete(string $id): void
    {
        $workspace = $this->scanWorkspaceAction->execute();

        $requestFile = $workspace->entries->findRequestOrFail($id);

        $this->deleteFile($requestFile->filepath);

        $newManifestFile = $workspace->manifest->removeRequest($requestFile);

        $this->writeWorkspaceFileAction->execute(
            data: WriteWorkspaceFileData::fromManifest($newManifestFile),
        );
    }

    private function deleteFile(string $filepath): void
    {
        $deleted = File::delete($filepath);

        if ($deleted) {
            return;
        }

        throw WorkspaceException::writeFailed();
    }
}
