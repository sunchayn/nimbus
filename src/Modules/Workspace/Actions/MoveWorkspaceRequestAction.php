<?php

declare(strict_types=1);

namespace Sunchayn\Nimbus\Modules\Workspace\Actions;

use Sunchayn\Nimbus\Modules\Workspace\DataTransferObjects\MoveWorkspaceRequestData;
use Sunchayn\Nimbus\Modules\Workspace\DataTransferObjects\RequestFile;
use Sunchayn\Nimbus\Modules\Workspace\DataTransferObjects\ResolveRequestFilepathData;
use Sunchayn\Nimbus\Modules\Workspace\DataTransferObjects\SavedRequest;
use Sunchayn\Nimbus\Modules\Workspace\DataTransferObjects\SaveRequestFileData;
use Sunchayn\Nimbus\Modules\Workspace\Exceptions\WorkspaceException;

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
    public function execute(MoveWorkspaceRequestData $data): RequestFile
    {
        return $this->lockWorkspaceAction->execute(
            fn (): RequestFile => $this->actuallyMove($data),
        );
    }

    private function actuallyMove(MoveWorkspaceRequestData $data): RequestFile
    {
        $workspace = $this->scanWorkspaceAction->execute();

        $previous = $workspace->entries->findRequestOrFail($data->id);

        $filepath = $this->resolveRequestFilepathAction->execute(
            data: new ResolveRequestFilepathData(
                folder: $data->folder,
                name: $data->name,
                current: $previous,
            ),
        );

        $savedRequest = $this->renameRequest(
            $previous->savedRequest,
            $data->name,
        );

        return $this->saveRequestFileAction->execute(
            data: new SaveRequestFileData(
                savedRequest: $savedRequest,
                filepath: $filepath,
                previous: $previous,
                hash: $data->hash,
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
