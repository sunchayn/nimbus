<?php

declare(strict_types=1);

namespace Sunchayn\Nimbus\Modules\Workspace\DataTransferObjects;

use Sunchayn\Nimbus\Modules\Workspace\ValueObjects\FolderPath;

class ReorderRequestsData
{
    /**
     * @param  list<string>  $ids  Request ids in the order they should be listed.
     */
    public function __construct(
        public readonly FolderPath $folder,
        public readonly array $ids,
    ) {}
}
