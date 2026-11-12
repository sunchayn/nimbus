<?php

declare(strict_types=1);

namespace Sunchayn\Nimbus\Modules\Workspace\Actions;

use Illuminate\Support\Facades\File;
use Sunchayn\Nimbus\Modules\Workspace\DataTransferObjects\SavedRequest;

/**
 * Reads the text of a workspace file.
 *
 * @final
 */
class ReadWorkspaceFileAction
{
    public function execute(string $filepath): ?string
    {
        if (! File::isFile($filepath)) {
            return null;
        }

        if (is_link($filepath)) {
            return null;
        }

        $size = File::size($filepath);

        if ($size > SavedRequest::MAX_FILE_SIZE_IN_BYTES) {
            return null;
        }

        $contents = File::get($filepath);

        return $contents ?: null;
    }
}
