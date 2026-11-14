<?php

declare(strict_types=1);

namespace Sunchayn\Nimbus\Modules\Workspace\Actions;

use Illuminate\Support\Facades\File;
use Sunchayn\Nimbus\Modules\Workspace\DataTransferObjects\SavedRequest;
use Sunchayn\Nimbus\Modules\Workspace\DataTransferObjects\WriteWorkspaceFileData;
use Sunchayn\Nimbus\Modules\Workspace\Exceptions\WorkspaceException;

/**
 * Writes a workspace file through a temporary file and returns the hash of its content.
 *
 * @final
 */
class WriteWorkspaceFileAction
{
    /**
     * @throws WorkspaceException
     */
    public function execute(WriteWorkspaceFileData $data): string
    {
        $contents = (string) $data->contents;

        if (strlen($contents) > SavedRequest::MAX_FILE_SIZE_IN_BYTES) {
            throw WorkspaceException::fileTooLarge();
        }

        $dirpath = dirname($data->filepath);

        $this->ensureDirectoryExists($dirpath);

        $this->writeThroughTemporaryFile(
            $dirpath,
            $data->filepath,
            $contents,
        );

        return hash('xxh3', $contents);
    }

    private function ensureDirectoryExists(string $dirpath): void
    {
        File::ensureDirectoryExists($dirpath);

        if (File::isDirectory($dirpath)) {
            return;
        }

        throw WorkspaceException::writeFailed();
    }

    private function writeThroughTemporaryFile(string $dirpath, string $filepath, string $contents): void
    {
        // The rename keeps readers from ever seeing a half written file.
        $uniqueId = uniqid(
            'workspace_',
            more_entropy: true,
        );

        $temporaryFilepath = "{$dirpath}/.{$uniqueId}.tmp";

        $written = @file_put_contents($temporaryFilepath, $contents);

        if ($written === false) {
            File::delete($temporaryFilepath);

            throw WorkspaceException::writeFailed();
        }

        $moved = @rename($temporaryFilepath, $filepath);

        if ($moved) {
            return;
        }

        File::delete($temporaryFilepath);

        throw WorkspaceException::writeFailed();
    }
}
