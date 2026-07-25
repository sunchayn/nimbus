<?php

declare(strict_types=1);

namespace Sunchayn\Nimbus\Modules\Extractor\Services\Response\Strategies;

use Illuminate\Routing\Route;
use Sunchayn\Nimbus\Modules\Ast\Actions\ResolveClassReturnTypeAction;
use Sunchayn\Nimbus\Modules\Extractor\Actions\InferSchemaFromSpatieDataObjectAction;
use Sunchayn\Nimbus\Modules\Extractor\Services\Response\Contracts\ResponseSchemaStrategyContract;
use Sunchayn\Nimbus\Modules\Schemas\ValueObjects\Schema;

/**
 * Extracts a response Schema from controllers that return a Spatie Data object.
 */
class SpatieDataObjectResponseStrategy implements ResponseSchemaStrategyContract
{
    public function __construct(
        private readonly InferSchemaFromSpatieDataObjectAction $inferSchemaFromSpatieDataObjectAction,
        private readonly ResolveClassReturnTypeAction $resolveClassReturnTypeAction,
    ) {}

    public function attempt(Route $route): ?Schema
    {
        // @codeCoverageIgnoreStart
        if (! class_exists(\Spatie\LaravelData\Data::class)) {
            return null;
        }

        // @codeCoverageIgnoreEnd

        /** @var class-string $controllerClass */
        $controllerClass = $route->getControllerClass();
        $methodName = $route->getActionMethod();

        if (empty($controllerClass) || empty($methodName)) {
            return null;
        }

        $returnType = $this->resolveClassReturnTypeAction->execute(
            className: $controllerClass,
            methodName: $methodName,
        );

        // @phpstan-ignore-next-line class-string is same as string (what is expected in is_subclass_of).
        if (! is_subclass_of($returnType, \Spatie\LaravelData\Data::class)) {
            return null;
        }

        $schema = $this->inferSchemaFromSpatieDataObjectAction->execute($returnType);

        return $schema->isEmpty() ? null : $schema;
    }
}
