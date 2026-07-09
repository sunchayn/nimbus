<?php

namespace Sunchayn\Nimbus\Http\Api\Collections;

use Illuminate\Foundation\Http\FormRequest;
use Sunchayn\Nimbus\Modules\Collections\DataTransferObjects\CollectionData;

class SyncCollectionsRequest extends FormRequest
{
    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'collections' => 'present|array',
            'collections.*.id' => 'required|string|max:255',
            'collections.*.name' => 'required|string|max:255',
            'collections.*.variables' => 'present|array',
            'collections.*.variables.*.id' => 'sometimes',
            'collections.*.variables.*.type' => 'sometimes|string',
            'collections.*.variables.*.key' => 'sometimes|nullable|string',
            'collections.*.variables.*.value' => 'sometimes|nullable',
            'collections.*.variables.*.enabled' => 'sometimes|boolean',
        ];
    }

    /**
     * The validated collections mapped to DTOs, in the order received.
     *
     * @return array<int, CollectionData>
     */
    public function collections(): array
    {
        /** @var array<int, array<string, mixed>> $collections */
        $collections = $this->validated('collections', []);

        return array_map(
            fn (array $collection, int $order): CollectionData => CollectionData::fromArray($collection, $order),
            $collections,
            array_keys($collections),
        );
    }
}
