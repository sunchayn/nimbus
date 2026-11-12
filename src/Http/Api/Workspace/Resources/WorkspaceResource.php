<?php

declare(strict_types=1);

namespace Sunchayn\Nimbus\Http\Api\Workspace\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Sunchayn\Nimbus\Modules\Workspace\DataTransferObjects\WorkspaceScanResult;
use Sunchayn\Nimbus\Modules\Workspace\ValueObjects\FolderPath;

/**
 * The folders and the requests of the workspace, in the order they are shown.
 *
 * @property WorkspaceScanResult $resource
 *
 * @mixin WorkspaceScanResult
 */
class WorkspaceResource extends JsonResource
{
    /** @var string|null */
    public static $wrap;

    /**
     * @return array{folders: array<int, string>, requests: mixed}
     */
    public function toArray(Request $request): array
    {
        $folders = $this
            ->resource
            ->entries
            ->folderPaths()
            ->map(static fn (FolderPath $folder): string => (string) $folder)
            ->all();

        return [
            'folders' => $folders,
            'requests' => WorkspaceRequestResource::collection($this->resource->entries->requestFiles()),
        ];
    }
}
