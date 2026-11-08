<?php

declare(strict_types=1);

namespace Sunchayn\Nimbus\Http\Api\Magic;

use Sunchayn\Nimbus\Http\Api\Responses\ResponseShapeRequest;

/**
 * Names a route, so the pairs it may read can only come from the exists rules of that route.
 */
class LookupValuesRequest extends ResponseShapeRequest
{
    /**
     * @return array<string, array<int, string>>
     */
    public function rules(): array
    {
        return [
            'method' => ['required', 'string'],
            'endpoint' => ['required', 'string'],
            'pairs' => ['required', 'array', 'max:20'],
            'pairs.*.table' => ['required', 'string'],
            'pairs.*.column' => ['required', 'string'],
        ];
    }

    /**
     * @return array<int, array{table: string, column: string}>
     */
    public function getPairs(): array
    {
        return array_map(
            fn (array $pair): array => ['table' => (string) $pair['table'], 'column' => (string) $pair['column']],
            array_values((array) $this->validated('pairs')),
        );
    }
}
