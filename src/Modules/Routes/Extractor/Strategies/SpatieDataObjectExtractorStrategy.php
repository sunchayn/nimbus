<?php

namespace Sunchayn\Nimbus\Modules\Routes\Extractor\Strategies;

use Illuminate\Container\Container;
use Illuminate\Support\Arr;
use ReflectionNamedType;
use ReflectionParameter;
use Sunchayn\Nimbus\Modules\Routes\ValueObjects\ExtractableRoute;
use Sunchayn\Nimbus\Modules\Routes\ValueObjects\RulesExtractionError;
use Sunchayn\Nimbus\Modules\Schemas\Builders\SchemaBuilder;
use Sunchayn\Nimbus\Modules\Schemas\Collections\Ruleset;
use Sunchayn\Nimbus\Modules\Schemas\ValueObjects\Schema;
use Throwable;

/**
 * Extracts validation rules from Spatie Data classes.
 */
class SpatieDataObjectExtractorStrategy implements ExtractorStrategyContract
{
    public function __construct(
        private readonly SchemaBuilder $schemaBuilder,
        private readonly Container $container,
    ) {}

    public function matches(ExtractableRoute $extractableRoute): bool
    {
        // @codeCoverageIgnoreStart
        if (! class_exists(\Spatie\LaravelData\Data::class)) {
            return false;
        }
        // @codeCoverageIgnoreEnd

        foreach ($extractableRoute->parameters as $parameter) {
            if (! $parameter->hasType()) {
                continue;
            }

            $type = $parameter->getType();

            if (! $type instanceof ReflectionNamedType) {
                continue;
            }

            $parameterType = $type->getName();

            // If it is not a form request instance, we continue.
            if (! is_subclass_of($parameterType, \Spatie\LaravelData\Data::class)) {
                continue;
            }

            return true;
        }

        return false; // <- didn't find a form request.
    }

    public function extract(ExtractableRoute $extractableRoute): Schema
    {
        /** @var ?ReflectionParameter $dataParameter */
        $dataParameter = Arr::first(
            $extractableRoute->parameters,
            function (ReflectionParameter $reflectionParameter): bool {
                $type = $reflectionParameter->getType();

                if (! $type instanceof ReflectionNamedType) {
                    return false;
                }

                return is_subclass_of($type->getName(), \Spatie\LaravelData\Data::class);
            },
        );

        if (! $dataParameter) {
            return Schema::empty();
        }

        /** @var ReflectionNamedType $type */
        $type = $dataParameter->getType();

        /** @var class-string $spatieDataObjectClassName */
        $spatieDataObjectClassName = $type->getName();

        try {
            $rules = $this
                ->container
                ->make(\Spatie\LaravelData\Resolvers\DataValidationRulesResolver::class)
                ->execute(
                    $spatieDataObjectClassName,
                    [],
                    \Spatie\LaravelData\Support\Validation\ValidationPath::create(),
                    \Spatie\LaravelData\Support\Validation\DataRules::create()
                );

            $ruleset = Ruleset::fromLaravelRules($rules);
        } catch (Throwable $throwable) {
            $throwable = new RulesExtractionError(
                throwable: $throwable,
            );

            $ruleset = Ruleset::empty();
        }

        return $this->schemaBuilder->buildSchemaFromRuleset($ruleset, rulesExtractionError: $throwable ?? null);
    }
}
