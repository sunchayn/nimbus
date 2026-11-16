<?php

declare(strict_types=1);

namespace Sunchayn\Nimbus\Modules\Workspace\Actions;

use Sunchayn\Nimbus\Modules\Workspace\DataTransferObjects\RequestFile;
use Sunchayn\Nimbus\Modules\Workspace\DataTransferObjects\SavedRequest;
use Sunchayn\Nimbus\Modules\Workspace\DataTransferObjects\SaveRequestFileData;
use Sunchayn\Nimbus\Modules\Workspace\Exceptions\WorkspaceException;
use Sunchayn\Nimbus\Modules\Workspace\ValueObjects\FolderPath;

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
    public function execute(
        SavedRequest $savedRequest,
        ?string $hash,
        FolderPath $folder,
    ): RequestFile {
        return $this->lockWorkspaceAction->execute(
            fn (): RequestFile => $this->actuallySave(
                savedRequest: $savedRequest,
                hash: $hash,
                folder: $folder,
            ),
        );
    }

    private function actuallySave(
        SavedRequest $savedRequest,
        ?string $hash,
        FolderPath $folder,
    ): RequestFile {
        $workspace = $this->scanWorkspaceAction->execute();

        $previous = $workspace->entries->findRequest($savedRequest->id);

        $filepath = $this->findFilepath(
            savedRequest: $savedRequest,
            folder: $folder,
            previous: $previous,
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

    private function findFilepath(
        SavedRequest $savedRequest,
        FolderPath $folder,
        ?RequestFile $previous,
    ): string {
        // An existing request stays where it is, so the folder only applies to a new one.
        if ($previous instanceof RequestFile) {
            return $previous->filepath;
        }

        return $this->resolveRequestFilepathAction->execute(
            folder: $folder,
            name: $savedRequest->name,
        );
    }
}
