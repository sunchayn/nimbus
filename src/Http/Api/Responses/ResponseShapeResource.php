<?php

declare(strict_types=1);

namespace Sunchayn\Nimbus\Http\Api\Responses;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Sunchayn\Nimbus\Modules\Schemas\ValueObjects\Schema;

/**
 * @property Schema $resource
 *
 * @mixin Schema
 */
class ResponseShapeResource extends JsonResource
{
    /** @var string|null */
    public static $wrap;

    /**
     * @return array{shape: array<string, mixed>}
     */
    public function toArray(Request $request): array
    {
        return [
            'shape' => $this->resource->toJsonSchema(),
        ];
    }
}
