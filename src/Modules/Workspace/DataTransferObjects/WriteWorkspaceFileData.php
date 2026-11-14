<?php

declare(strict_types=1);

namespace Sunchayn\Nimbus\Modules\Workspace\DataTransferObjects;

use Stringable;
use Sunchayn\Nimbus\Modules\Workspace\ValueObjects\WorkspaceConfig;

class WriteWorkspaceFileData
{
    public function __construct(
        public readonly string $filepath,
        public readonly Stringable $contents,
    ) {}

    public static function fromManifest(ManifestFile $manifest): self
    {
        return new self(
            filepath: resolve(WorkspaceConfig::class)->manifestPath(),
            contents: $manifest,
        );
    }
}
