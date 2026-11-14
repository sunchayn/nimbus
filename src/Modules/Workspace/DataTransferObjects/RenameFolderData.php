<?php

declare(strict_types=1);

namespace Sunchayn\Nimbus\Modules\Workspace\DataTransferObjects;

use Sunchayn\Nimbus\Modules\Workspace\ValueObjects\FolderPath;

class RenameFolderData
{
    /**
     * @param  FolderPath  $name  The new name, which is a path with a single segment.
     */
    public function __construct(
        public readonly FolderPath $folder,
        public readonly FolderPath $name,
    ) {}
}
