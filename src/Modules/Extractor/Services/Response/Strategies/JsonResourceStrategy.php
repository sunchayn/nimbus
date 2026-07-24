<?php

declare(strict_types=1);

namespace Sunchayn\Nimbus\Modules\Extractor\Services\Response\Strategies;

use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Routing\Route;
use Sunchayn\Nimbus\Modules\Ast\Actions\ResolveClassReturnTypeAction;
use Sunchayn\Nimbus\Modules\Extractor\Actions\InferSchemaFromResourceAstAction;
use Sunchayn\Nimbus\Modules\Extractor\Services\Response\Contracts\ResponseSchemaStrategyContract;
use Sunchayn\Nimbus\Modules\Schemas\ValueObjects\Schema;

/**
 * Extracts a response Schema from controllers that return a Laravel JsonResource.
 */
class JsonResourceStrategy implements ResponseSchemaStrategyContract
{
    public function __construct(
        private readonly InferSchemaFromResourceAstAction $inferSchemaFromResourceAstAction,
        private readonly ResolveClassReturnTypeAction $resolveClassReturnTypeAction,
    ) {}

    public function attempt(Route $route): ?Schema
    {
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
        if ($returnType !== JsonResource::class && ! is_subclass_of($returnType, JsonResource::class)) {
            return null;
        }

        $schema = $this->inferSchemaFromResourceAstAction->execute($returnType);

        return $schema->isEmpty() ? null : $schema;
    }
}
