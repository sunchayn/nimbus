<?php

declare(strict_types=1);

namespace Sunchayn\Nimbus\Modules\Extractor\Services\Request\Strategies;

use Illuminate\Http\Request;
use Illuminate\Routing\Route;
use ReflectionNamedType;
use ReflectionParameter;
use Sunchayn\Nimbus\Modules\Ast\Queries\ClassQuery;
use Sunchayn\Nimbus\Modules\Extractor\Services\Request\Contracts\RequestSchemaStrategyContract;
use Sunchayn\Nimbus\Modules\Extractor\Services\Request\Strategies\Concerns\ResolvesRouteParameters;
use Sunchayn\Nimbus\Modules\Schemas\Collections\Ruleset;
use Sunchayn\Nimbus\Modules\Schemas\Services\Builders\SchemaBuilder;
use Sunchayn\Nimbus\Modules\Schemas\ValueObjects\RulesExtractionError;
use Sunchayn\Nimbus\Modules\Schemas\ValueObjects\Schema;
use Throwable;

/**
 * Extracts validation rules from Laravel Form Request classes.
 */
class FormRequestStrategy implements RequestSchemaStrategyContract
{
    use ResolvesRouteParameters;

    public function __construct(
        private readonly SchemaBuilder $schemaBuilder,
    ) {}

    public function attempt(Route $route): ?Schema
    {
        $parameters = $this->getRouteParameters($route);

        $requestParameter = $this->findFormRequestParameter($parameters);

        if (! $requestParameter instanceof ReflectionParameter) {
            return null;
        }

        /** @var ReflectionNamedType $type */
        $type = $requestParameter->getType();

        /** @var class-string $requestClassName */
        $requestClassName = $type->getName();

        $instance = new $requestClassName;

        if (! method_exists($instance, 'rules')) {
            return null;
        }

        try {
            $rules = Ruleset::fromLaravelRules($instance->rules());
            $error = null;
        } catch (Throwable $throwable) {
            // rules() can fail when it calls $this->user() or other context-dependent methods.
            // Fall back to static AST analysis to extract the rules' array shape without runtime context.
            $rules = $this->attemptGettingRulesShape($requestClassName);
            $error = new RulesExtractionError($throwable);
        }

        $schema = $this->schemaBuilder->buildSchemaFromRuleset($rules, $error);

        return $schema->isEmpty() ? null : $schema;

    }

    /**
     * @param  ReflectionParameter[]  $parameters
     */
    private function findFormRequestParameter(array $parameters): ?ReflectionParameter
    {
        return $this->findChildOf($parameters, Request::class);
    }

    /**
     * @param  class-string  $requestClassName
     */
    private function attemptGettingRulesShape(string $requestClassName): Ruleset
    {
        $rules = ClassQuery::from($requestClassName)->method('rules')?->getConcreteReturnValue();

        if (is_array($rules)) {
            return Ruleset::fromLaravelRules($rules);
        }

        return Ruleset::empty();
    }
}
