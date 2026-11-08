<?php

declare(strict_types=1);

namespace Sunchayn\Nimbus\Http\Api\Magic;

use Illuminate\Http\JsonResponse;
use Sunchayn\Nimbus\Modules\Extractor\Services\Request\RequestSchemaExtractor;
use Sunchayn\Nimbus\Modules\Magic\Actions\ReadLookupValuesAction;

class LookupValuesController
{
    public function __construct(
        private readonly RequestSchemaExtractor $requestSchemaExtractor,
        private readonly ReadLookupValuesAction $readLookupValuesAction,
    ) {}

    public function __invoke(LookupValuesRequest $lookupValuesRequest): JsonResponse
    {
        $route = $lookupValuesRequest->getTargetRoute();

        $schema = $this->requestSchemaExtractor->extract($route)->toJsonSchema();

        return response()->json([
            'values' => (object) $this->readLookupValuesAction->execute(
                allowed: $this->collectLookups($schema),
                requested: $lookupValuesRequest->getPairs(),
            ),
        ]);
    }

    /**
     * @param  array<array-key, mixed>  $schema
     * @return array<int, array{table: string, column: string}>
     */
    private function collectLookups(array $schema): array
    {
        $lookups = [];

        foreach ($schema as $key => $value) {
            if ($key === 'lookup' && is_array($value) && isset($value['table'], $value['column'])) {
                $lookups[] = ['table' => (string) $value['table'], 'column' => (string) $value['column']];
            } elseif (is_array($value)) {
                $lookups = [...$lookups, ...$this->collectLookups($value)];
            }
        }

        return $lookups;
    }
}
