<?php

declare(strict_types=1);

namespace Sunchayn\Nimbus\Modules\Extractor\Actions;

use Illuminate\Container\Container;
use Illuminate\Contracts\Container\BindingResolutionException;
use Sunchayn\Nimbus\Modules\Schemas\Builders\SchemaBuilder;
use Sunchayn\Nimbus\Modules\Schemas\Collections\Ruleset;
use Sunchayn\Nimbus\Modules\Schemas\ValueObjects\RulesExtractionError;
use Sunchayn\Nimbus\Modules\Schemas\ValueObjects\Schema;
use Throwable;

/**
 * Converts a Spatie Data class into a Schema object.
 *
 * @final
 */
class InferSchemaFromSpatieDataObjectAction
{
    public function __construct(
        private readonly SchemaBuilder $schemaBuilder,
        private readonly Container $container,
    ) {}

    /**
     * @param  class-string  $spatieDataClass
     */
    public function execute(string $spatieDataClass): Schema
    {
        // @codeCoverageIgnoreStart
        if (! class_exists(\Spatie\LaravelData\Data::class)) {
            return Schema::empty();
        }

        // @codeCoverageIgnoreEnd

        try {
            $rules = $this->getRules($spatieDataClass);
            $ruleset = Ruleset::fromLaravelRules($rules);

            return $this->schemaBuilder->buildSchemaFromRuleset($ruleset);
        } catch (Throwable $throwable) {
            return $this->schemaBuilder->buildSchemaFromRuleset(
                Ruleset::empty(),
                new RulesExtractionError($throwable)
            );
        }
    }

    /**
     * Resolves the raw validation rules array for a Spatie Data class.
     *
     * @param  class-string  $spatieDataClass
     * @return array<string, mixed>
     *
     * @throws BindingResolutionException
     */
    private function getRules(string $spatieDataClass): array
    {
        return $this
            ->container
            ->make(\Spatie\LaravelData\Resolvers\DataValidationRulesResolver::class)
            ->execute(
                class: $spatieDataClass,
                fullPayload: [],
                path: \Spatie\LaravelData\Support\Validation\ValidationPath::create(),
                dataRules: \Spatie\LaravelData\Support\Validation\DataRules::create()
            );
    }
}
