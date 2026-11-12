<?php

declare(strict_types=1);

namespace Sunchayn\Nimbus\Modules\Workspace\DataTransferObjects;

use Sunchayn\Nimbus\Modules\Workspace\ValueObjects\FolderPath;

class ResolveRequestFilepathData
{
    /**
     * @param  FolderPath|null  $folder  The folder of the file, or null to keep the folder of the current file.
     * @param  string|null  $name  The name of the request, or null to keep the name of the current file.
     * @param  RequestFile|null  $current  The file that is being saved or moved, which counts as a free path.
     */
    public function __construct(
        public readonly ?FolderPath $folder,
        public readonly ?string $name,
        public readonly ?RequestFile $current = null,
    ) {}
}
