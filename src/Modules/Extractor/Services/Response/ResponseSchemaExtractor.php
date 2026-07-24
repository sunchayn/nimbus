<?php

declare(strict_types=1);

namespace Sunchayn\Nimbus\Modules\Extractor\Services\Response;

use Illuminate\Container\Container;
use Illuminate\Contracts\Container\BindingResolutionException;
use Illuminate\Routing\Route;
use Sunchayn\Nimbus\Modules\Extractor\Actions\InferSchemaFromArrayAction;
use Sunchayn\Nimbus\Modules\Extractor\Services\Response\Contracts\ResponseSchemaStrategyContract;
use Sunchayn\Nimbus\Modules\Extractor\Services\Response\DataTransferObjects\ExtractableResponseData;
use Sunchayn\Nimbus\Modules\Extractor\Services\Response\Strategies\JsonResourceStrategy;
use Sunchayn\Nimbus\Modules\Extractor\Services\Response\Strategies\RawJsonResponseStrategy;
use Sunchayn\Nimbus\Modules\Extractor\Services\Response\Strategies\SpatieDataObjectResponseStrategy;
use Sunchayn\Nimbus\Modules\Schemas\ValueObjects\Schema;

/**
 * Extracts a response Schema for a given route by trying strategies in priority order.
 */
class ResponseSchemaExtractor
{
    /** @var ResponseSchemaStrategyContract[] */
    private readonly array $strategies;

    /**
     * @throws BindingResolutionException
     */
    public function __construct(
        Container $container,
        private readonly InferSchemaFromArrayAction $inferSchemaFromArrayAction,
    ) {
        $this->strategies = [
            $container->make(JsonResourceStrategy::class),
            $container->make(SpatieDataObjectResponseStrategy::class),
            $container->make(RawJsonResponseStrategy::class),
        ];
    }

    /**
     * Extracts a response Schema by attempting each strategy in priority order.
     *
     * Falls back to ConvertConcretePayloadToSchemaAction if no AST-based strategy succeeds,
     * which derives the schema from the runtime payload when available.
     */
    public function extract(ExtractableResponseData $data): Schema
    {
        if (! $data->route instanceof Route) {
            return $this->inferSchemaFromArrayAction->execute($data->payload);
        }

        foreach ($this->strategies as $strategy) {
            $schema = $strategy->attempt($data->route);

            if ($schema !== null) {
                return $schema;
            }
        }

        // If no AST-based strategy succeeds, we infer the schema structure directly from a concrete payload.
        return $this->inferSchemaFromArrayAction->execute($data->payload);
    }
}
