<?php

declare(strict_types=1);

namespace Sunchayn\Nimbus\Modules\Workspace\Actions;

use Illuminate\Support\Facades\File;
use Sunchayn\Nimbus\Modules\Workspace\Exceptions\WorkspaceException;
use Sunchayn\Nimbus\Modules\Workspace\ValueObjects\FolderPath;
use Sunchayn\Nimbus\Modules\Workspace\ValueObjects\WorkspaceConfig;

/**
 * Finds the directory of a folder on the disk.
 *
 * @final
 */
class FindWorkspaceFolderDirectoryAction
{
    public function __construct(
        private readonly WorkspaceConfig $config,
    ) {}

    /**
     * @throws WorkspaceException
     */
    public function execute(FolderPath $folder): string
    {
        $dirpath = $this->config->folderDirectory($folder);

        if (is_link($dirpath)) {
            throw WorkspaceException::folderNotFound((string) $folder);
        }

        if (! File::isDirectory($dirpath)) {
            throw WorkspaceException::folderNotFound((string) $folder);
        }

        return $dirpath;
    }
}
