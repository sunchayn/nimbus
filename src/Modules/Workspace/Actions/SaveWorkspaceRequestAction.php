<?php

declare(strict_types=1);

namespace Sunchayn\Nimbus\Modules\Workspace\Actions;

use Sunchayn\Nimbus\Modules\Workspace\DataTransferObjects\RequestFile;
use Sunchayn\Nimbus\Modules\Workspace\DataTransferObjects\ResolveRequestFilepathData;
use Sunchayn\Nimbus\Modules\Workspace\DataTransferObjects\SaveRequestFileData;
use Sunchayn\Nimbus\Modules\Workspace\DataTransferObjects\SaveWorkspaceRequestData;
use Sunchayn\Nimbus\Modules\Workspace\Exceptions\WorkspaceException;

/**
 * Creates a request file, or updates the file that already holds the request id.
 *
 * @final
 */
class SaveWorkspaceRequestAction
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
    public function execute(SaveWorkspaceRequestData $data): RequestFile
    {
        return $this->lockWorkspaceAction->execute(
            fn (): RequestFile => $this->actuallySave($data),
        );
    }

    private function actuallySave(SaveWorkspaceRequestData $data): RequestFile
    {
        $workspace = $this->scanWorkspaceAction->execute();

        $previous = $workspace->entries->findRequest($data->savedRequest->id);

        $filepath = $this->findFilepath(
            $data,
            $previous,
        );

        return $this->saveRequestFileAction->execute(
            data: new SaveRequestFileData(
                savedRequest: $data->savedRequest,
                filepath: $filepath,
                previous: $previous,
                hash: $data->hash,
                manifest: $workspace->manifest,
            ),
        );
    }

    private function findFilepath(SaveWorkspaceRequestData $data, ?RequestFile $previous): string
    {
        // An existing request stays where it is, so the folder only applies to a new one.
        if ($previous instanceof RequestFile) {
            return $previous->filepath;
        }

        return $this->resolveRequestFilepathAction->execute(
            data: new ResolveRequestFilepathData(
                folder: $data->folder,
                name: $data->savedRequest->name,
            ),
        );
    }
}
