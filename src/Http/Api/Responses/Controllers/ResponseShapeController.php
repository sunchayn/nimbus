<?php

declare(strict_types=1);

namespace Sunchayn\Nimbus\Http\Api\Responses\Controllers;

use Sunchayn\Nimbus\Http\Api\Responses\Requests\ResponseShapeRequest;
use Sunchayn\Nimbus\Http\Api\Responses\Resources\ResponseShapeResource;
use Sunchayn\Nimbus\Modules\Extractor\Services\Response\DataTransferObjects\ExtractableResponseData;
use Sunchayn\Nimbus\Modules\Extractor\Services\Response\ResponseSchemaExtractor;

class ResponseShapeController
{
    public function __construct(
        private readonly ResponseSchemaExtractor $responseSchemaExtractor,
    ) {}

    public function __invoke(ResponseShapeRequest $responseShapeRequest): ResponseShapeResource
    {
        $schema = $this
            ->responseSchemaExtractor
            ->extract(
                data: new ExtractableResponseData(
                    route: $responseShapeRequest->getTargetRoute(),
                    payload: $responseShapeRequest->getResponseBody(),
                )
            );

        return ResponseShapeResource::make($schema);
    }
}
