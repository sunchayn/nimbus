<?php

declare(strict_types=1);

namespace Sunchayn\Nimbus\Modules\Workspace\Actions;

use Illuminate\Support\Facades\File;
use Sunchayn\Nimbus\Modules\Workspace\DataTransferObjects\ManifestFile;
use Sunchayn\Nimbus\Modules\Workspace\DataTransferObjects\RequestFile;
use Sunchayn\Nimbus\Modules\Workspace\DataTransferObjects\SaveRequestFileData;
use Sunchayn\Nimbus\Modules\Workspace\DataTransferObjects\WriteWorkspaceFileData;
use Sunchayn\Nimbus\Modules\Workspace\Exceptions\WorkspaceException;
use Sunchayn\Nimbus\Modules\Workspace\ValueObjects\WorkspaceConfig;

/**
 * Writes a request file at a path and updates the manifest.
 *
 * @final
 */
class SaveRequestFileAction
{
    public function __construct(
        private readonly WorkspaceConfig $config,
        private readonly WriteWorkspaceFileAction $writeWorkspaceFileAction,
    ) {}

    /**
     * @throws WorkspaceException
     */
    public function execute(SaveRequestFileData $data): RequestFile
    {
        // The write is refused when the file changed since the caller loaded it.
        if ($data->hash !== $data->previous?->hash) {
            throw WorkspaceException::conflict();
        }

        $hash = $this->writeWorkspaceFileAction->execute(
            data: new WriteWorkspaceFileData(
                filepath: $data->filepath,
                contents: $data->savedRequest,
            ),
        );

        $this->deleteStaleFile($data);

        $folder = $this->config->folderOf($data->filepath);

        $requestFile = new RequestFile(
            filepath: $data->filepath,
            relativeFilepath: $this->config->relativeFilepath($data->filepath),
            folder: $folder,
            savedRequest: $data->savedRequest,
            hash: $hash,
        );

        $manifest = $this->placeInManifest(
            $data,
            $requestFile,
        );

        $this->writeWorkspaceFileAction->execute(
            data: WriteWorkspaceFileData::fromManifest($manifest),
        );

        return $requestFile;
    }

    private function deleteStaleFile(SaveRequestFileData $data): void
    {
        if (! $data->previous instanceof RequestFile) {
            return;
        }

        if ($data->previous->filepath === $data->filepath) {
            return;
        }

        // A rename or a move leaves the old file behind unless it is removed.
        File::delete($data->previous->filepath);
    }

    private function placeInManifest(SaveRequestFileData $data, RequestFile $requestFile): ManifestFile
    {
        if ($this->stayedInFolder(
            $data->previous,
            $requestFile,
        )) {
            return $data->manifest;
        }

        $manifest = $data->manifest;

        if ($data->previous instanceof RequestFile) {
            $manifest = $manifest->removeRequest($data->previous);
        }

        // A request that is new in its folder goes to the end of the list.
        return $manifest->addRequest($requestFile);
    }

    private function stayedInFolder(?RequestFile $previous, RequestFile $requestFile): bool
    {
        if (! $previous instanceof RequestFile) {
            return false;
        }

        return $previous->folder->equals($requestFile->folder);
    }
}
