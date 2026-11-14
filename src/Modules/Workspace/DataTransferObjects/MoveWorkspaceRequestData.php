<?php

declare(strict_types=1);

namespace Sunchayn\Nimbus\Modules\Workspace\DataTransferObjects;

use Sunchayn\Nimbus\Modules\Workspace\ValueObjects\FolderPath;

class MoveWorkspaceRequestData
{
    /**
     * @param  FolderPath|null  $folder  The target folder, or null to keep the folder.
     * @param  string|null  $name  The new name, or null to keep the name.
     * @param  string|null  $hash  The hash the caller loaded the file with.
     */
    public function __construct(
        public readonly string $id,
        public readonly ?FolderPath $folder,
        public readonly ?string $name,
        public readonly ?string $hash,
    ) {}
}
