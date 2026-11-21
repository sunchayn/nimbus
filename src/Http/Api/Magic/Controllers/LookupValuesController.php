<?php

declare(strict_types=1);

namespace Sunchayn\Nimbus\Http\Api\Magic\Controllers;

use Illuminate\Http\JsonResponse;
use Sunchayn\Nimbus\Http\Api\Magic\Requests\LookupValuesRequest;
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

        $schema = $this->requestSchemaExtractor->extract($route);

        return response()->json([
            'values' => (object) $this->readLookupValuesAction->execute(
                allowed: $schema->listLookups(),
                requested: $lookupValuesRequest->getPairs(),
            ),
        ]);
    }
}
