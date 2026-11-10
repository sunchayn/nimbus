<?php

namespace Workbench\App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class DemoPostResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => 1,
            'title' => 'Hello',
            'internal_note' => $this->when($request->user()?->isAdmin(), 'visible to admins'),
            'views' => $this->when(true, fn () => 10),
            'category' => new CategoryResource($this->whenLoaded('category')),
            $this->mergeWhen($request->boolean('full'), [
                'body' => 'Long text',
            ]),
        ];
    }
}
