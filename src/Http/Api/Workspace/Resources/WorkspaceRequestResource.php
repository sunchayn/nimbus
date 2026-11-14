<?php

declare(strict_types=1);

namespace Sunchayn\Nimbus\Http\Api\Workspace\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Sunchayn\Nimbus\Modules\Workspace\DataTransferObjects\RequestFile;
use Sunchayn\Nimbus\Modules\Workspace\ValueObjects\WorkspaceConfig;

/**
 * What the workspace list shows about a request.
 *
 * @property RequestFile $resource
 *
 * @mixin RequestFile
 */
class WorkspaceRequestResource extends JsonResource
{
    /** @var string|null */
    public static $wrap;

    /**
     * @return array{id: string, name: string, method: string, endpoint: string, route: string|null, file: string, folder: string, hash: string}
     */
    public function toArray(Request $request): array
    {
        $config = resolve(WorkspaceConfig::class);

        return [
            'id' => $this->resource->savedRequest->id,
            'name' => $this->resource->savedRequest->name,
            'method' => $this->resource->savedRequest->method,
            'endpoint' => $this->resource->savedRequest->endpoint,
            'route' => $this->resource->savedRequest->route,
            'file' => $config->relativeFilepath($this->resource->filepath),
            'folder' => (string) $this->resource->folder,
            'hash' => $this->resource->hash,
        ];
    }
}
