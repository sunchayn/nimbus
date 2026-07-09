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

            // Saved requests are shareable-request payloads loaded straight into
            // a request tab, so the fields the restore path dereferences
            // (headers, queryParameters, authorization.type) must be present.
            // `present` (not `required`) allows empty arrays. Each leaf we want
            // to survive validated() needs a rule; the array rule on `body`
            // carries its whole nested value through untouched. `response` and
            // `requestLog` are deliberately not persisted (runtime output, and
            // `response` can carry decrypted cookie values).
            'collections.*.requests' => 'sometimes|array',
            'collections.*.requests.*.id' => 'required|string|max:255',
            'collections.*.requests.*.name' => 'required|string|max:255',
            'collections.*.requests.*.method' => 'required|string',
            'collections.*.requests.*.endpoint' => 'required|string',
            'collections.*.requests.*.payloadType' => 'required|string',
            'collections.*.requests.*.headers' => 'present|array',
            'collections.*.requests.*.queryParameters' => 'present|array',
            'collections.*.requests.*.body' => 'sometimes|array',
            'collections.*.requests.*.authorization' => 'present|array',
            'collections.*.requests.*.authorization.type' => 'required|string',
            // No type constraint: bearer holds a string, impersonate a number,
            // basic a {username, password} object. Without this rule validated()
            // silently drops the value and every saved request loses its auth.
            'collections.*.requests.*.authorization.value' => 'sometimes|nullable',
            'collections.*.requests.*.applicationKey' => 'sometimes|string',
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
