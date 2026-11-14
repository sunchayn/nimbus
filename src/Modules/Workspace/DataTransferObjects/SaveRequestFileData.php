<?php

declare(strict_types=1);

namespace Sunchayn\Nimbus\Modules\Workspace\DataTransferObjects;

class SaveRequestFileData
{
    /**
     * @param  string  $filepath  Where the request file is written.
     * @param  RequestFile|null  $previous  The file the request is read from, which is removed when the path changes.
     * @param  string|null  $hash  The hash the caller loaded the previous file with.
     * @param  ManifestFile  $manifest  The manifest the caller loaded together with the previous file.
     */
    public function __construct(
        public readonly SavedRequest $savedRequest,
        public readonly string $filepath,
        public readonly ?RequestFile $previous,
        public readonly ?string $hash,
        public readonly ManifestFile $manifest,
    ) {}
}
