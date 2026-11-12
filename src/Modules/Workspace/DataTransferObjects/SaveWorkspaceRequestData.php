<?php

declare(strict_types=1);

namespace Sunchayn\Nimbus\Modules\Workspace\DataTransferObjects;

use Sunchayn\Nimbus\Modules\Workspace\ValueObjects\FolderPath;

class SaveWorkspaceRequestData
{
    /**
     * @param  string|null  $hash  The hash the caller loaded the file with, null for a new file.
     * @param  FolderPath  $folder  The folder of a new file. An existing file stays where it is.
     */
    public function __construct(
        public readonly SavedRequest $savedRequest,
        public readonly ?string $hash,
        public readonly FolderPath $folder,
    ) {}
}
