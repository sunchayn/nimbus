<?php

declare(strict_types=1);

namespace Sunchayn\Nimbus\Modules\Workspace\Actions;

use Sunchayn\Nimbus\Modules\Workspace\DataTransferObjects\RequestFile;
use Sunchayn\Nimbus\Modules\Workspace\DataTransferObjects\SavedRequest;
use Sunchayn\Nimbus\Modules\Workspace\DataTransferObjects\SaveRequestFileData;
use Sunchayn\Nimbus\Modules\Workspace\Exceptions\WorkspaceException;
use Sunchayn\Nimbus\Modules\Workspace\ValueObjects\FolderPath;

/**
 * Moves a request to another folder, renames it, or both.
 *
 * @final
 */
class MoveWorkspaceRequestAction
{
    public function __construct(
        private readonly LockWorkspaceAction $lockWorkspaceAction,
        private readonly ScanWorkspaceAction $scanWorkspaceAction,
        private readonly ResolveRequestFilepathAction $resolveRequestFilepathAction,
        private readonly SaveRequestFileAction $saveRequestFileAction,
    ) {}

    /**
     * @throws WorkspaceException
     */
    public function execute(
        string $id,
        ?FolderPath $folder,
        ?string $name,
        ?string $hash,
    ): RequestFile {
        return $this->lockWorkspaceAction->execute(
            fn (): RequestFile => $this->actuallyMove(
                id: $id,
                folder: $folder,
                name: $name,
                hash: $hash,
            ),
        );
    }

    private function actuallyMove(
        string $id,
        ?FolderPath $folder,
        ?string $name,
        ?string $hash,
    ): RequestFile {
        $workspace = $this->scanWorkspaceAction->execute();

        $previous = $workspace->entries->findRequestOrFail($id);

        $filepath = $this->resolveRequestFilepathAction->execute(
            folder: $folder,
            name: $name,
            current: $previous,
        );

        $savedRequest = $this->renameRequest(
            $previous->savedRequest,
            $name,
        );

        return $this->saveRequestFileAction->execute(
            data: new SaveRequestFileData(
                savedRequest: $savedRequest,
                filepath: $filepath,
                previous: $previous,
                hash: $hash,
                manifest: $workspace->manifest,
            ),
        );
    }

    private function renameRequest(SavedRequest $savedRequest, ?string $name): SavedRequest
    {
        if ($name === null) {
            return $savedRequest;
        }

        return $savedRequest->withName($name);
    }
}
