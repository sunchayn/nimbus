<?php

declare(strict_types=1);

namespace Sunchayn\Nimbus\Modules\Extractor\Services\Request\Strategies;

use Illuminate\Routing\Route;
use ReflectionNamedType;
use ReflectionParameter;
use Sunchayn\Nimbus\Modules\Extractor\Actions\InferSchemaFromSpatieDataObjectAction;
use Sunchayn\Nimbus\Modules\Extractor\Services\Request\Contracts\RequestSchemaStrategyContract;
use Sunchayn\Nimbus\Modules\Extractor\Services\Request\Strategies\Concerns\ResolvesRouteParameters;
use Sunchayn\Nimbus\Modules\Schemas\ValueObjects\Schema;

/**
 * Extracts validation rules from Spatie Data objects used as controller parameters.
 */
class SpatieDataObjectStrategy implements RequestSchemaStrategyContract
{
    use ResolvesRouteParameters;

    public function __construct(
        private readonly InferSchemaFromSpatieDataObjectAction $inferSchemaFromSpatieDataObjectAction,
    ) {}

    public function attempt(Route $route): ?Schema
    {
        // @codeCoverageIgnoreStart
        if (! class_exists(\Spatie\LaravelData\Data::class)) {
            return null;
        }

        // @codeCoverageIgnoreEnd

        $parameters = $this->getRouteParameters($route);

        $dataParameter = $this->findSpatieDataParameter($parameters);

        if (! $dataParameter instanceof ReflectionParameter) {
            return null;
        }

        /** @var ReflectionNamedType $type */
        $type = $dataParameter->getType();

        /** @var class-string $spatieDataObjectClassName */
        $spatieDataObjectClassName = $type->getName();

        $schema = $this->inferSchemaFromSpatieDataObjectAction->execute($spatieDataObjectClassName);

        return $schema->isEmpty() ? null : $schema;
    }

    /**
     * @param  ReflectionParameter[]  $parameters
     */
    private function findSpatieDataParameter(array $parameters): ?ReflectionParameter
    {
        return $this->findChildOf($parameters, \Spatie\LaravelData\Data::class);
    }
}
