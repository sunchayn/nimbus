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

            // Saved requests are shareable-request payloads. Each leaf we want to
            // survive validated() needs a rule; the array rules on `body`,
            // `headers`, etc. carry their whole nested value through untouched.
            'collections.*.requests' => 'sometimes|array',
            'collections.*.requests.*.id' => 'required|string|max:255',
            'collections.*.requests.*.name' => 'required|string|max:255',
            'collections.*.requests.*.method' => 'required|string',
            'collections.*.requests.*.endpoint' => 'required|string',
            'collections.*.requests.*.payloadType' => 'sometimes|string',
            'collections.*.requests.*.headers' => 'sometimes|array',
            'collections.*.requests.*.queryParameters' => 'sometimes|array',
            'collections.*.requests.*.body' => 'sometimes|array',
            'collections.*.requests.*.authorization' => 'sometimes|array',
            'collections.*.requests.*.response' => 'sometimes|array',
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
