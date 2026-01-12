<?php

namespace Sunchayn\Nimbus\Modules\Schemas\Builders;

use Sunchayn\Nimbus\Modules\Schemas\Contracts\SchemaPropertyInterface;
use Sunchayn\Nimbus\Modules\Schemas\Enums\SchemaPropertyType;
use Sunchayn\Nimbus\Modules\Schemas\Enums\StringFormat;
use Sunchayn\Nimbus\Modules\Schemas\RulesMapper\RuleToSchemaMapper;
use Sunchayn\Nimbus\Modules\Schemas\ValueObjects\ArraySchemaProperty;
use Sunchayn\Nimbus\Modules\Schemas\ValueObjects\BooleanSchemaProperty;
use Sunchayn\Nimbus\Modules\Schemas\ValueObjects\IntegerSchemaProperty;
use Sunchayn\Nimbus\Modules\Schemas\ValueObjects\NumberSchemaProperty;
use Sunchayn\Nimbus\Modules\Schemas\ValueObjects\ObjectSchemaProperty;
use Sunchayn\Nimbus\Modules\Schemas\ValueObjects\Schema;
use Sunchayn\Nimbus\Modules\Schemas\ValueObjects\StringSchemaProperty;

/**
 * Converts Laravel validation rules into individual schema properties.
 *
 * Maps Laravel rule strings like "required|string|max:255" to JSON Schema properties
 * with proper type, format, and validation constraints.
 *
 * @example
 * Input:  "email" field with "required|email" rules
 * Output: SchemaProperty with type="string", format="email", required=true
 *
 * @phpstan-import-type NormalizedRulesShape from \Sunchayn\Nimbus\Modules\Schemas\Collections\Ruleset
 */
class PropertyBuilder
{
    public function __construct(
        private readonly RuleToSchemaMapper $ruleToSchemaMapper,
    ) {}

    /**
     * @param  NormalizedRulesShape  $rules
     */
    public function buildPropertyFromRules(string $field, array $rules): SchemaPropertyInterface
    {
        $schemaMetadata = $this->ruleToSchemaMapper->convertRulesToBaseSchemaPropertyMetadata($rules);

        return match ($schemaMetadata['type']) {
            SchemaPropertyType::STRING => new StringSchemaProperty(
                name: $field,
                required: $schemaMetadata['required'],
                stringFormat: $this->extractFormat($rules),
                enum: $schemaMetadata['enum'] ?? null,
                minLength: $schemaMetadata['minimum'],
                maxLength: $schemaMetadata['maximum'],
            ),
            SchemaPropertyType::INTEGER => new IntegerSchemaProperty(
                name: $field,
                required: $schemaMetadata['required'],
                minimum: $schemaMetadata['minimum'],
                maximum: $schemaMetadata['maximum'],
                enum: $schemaMetadata['enum'] ?? null,
            ),
            SchemaPropertyType::NUMBER => new NumberSchemaProperty(
                name: $field,
                required: $schemaMetadata['required'],
                minimum: $schemaMetadata['minimum'],
                maximum: $schemaMetadata['maximum'],
            ),
            SchemaPropertyType::BOOLEAN => new BooleanSchemaProperty(
                name: $field,
                required: $schemaMetadata['required'],
            ),
            SchemaPropertyType::ARRAY => new ArraySchemaProperty(
                name: $field,
                required: $schemaMetadata['required'],
                schemaProperty: null, // <- Items will be set later by SchemaBuilder if needed.
            ),
            SchemaPropertyType::OBJECT => new ObjectSchemaProperty(
                name: $field,
                required: $schemaMetadata['required'],
                schema: new Schema([]), // <- Properties will be set later by SchemaBuilder if needed.
            ),
        };
    }

    /**
     * @param  NormalizedRulesShape  $rules
     */
    private function extractFormat(array $rules): ?StringFormat
    {
        foreach ($rules as $rule) {
            if (! is_string($rule)) {
                continue;
            }

            $format = $this->detectFormatFromRule($rule);

            if ($format instanceof \Sunchayn\Nimbus\Modules\Schemas\Enums\StringFormat) {
                return $format;
            }
        }

        return null;
    }

    private function detectFormatFromRule(string $rule): ?StringFormat
    {
        return StringFormat::fromRule($rule);
    }
}
