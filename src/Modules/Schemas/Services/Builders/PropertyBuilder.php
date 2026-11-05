<?php

namespace Sunchayn\Nimbus\Modules\Schemas\Services\Builders;

use Illuminate\Support\Str;
use Illuminate\Validation\Rules\File;
use Sunchayn\Nimbus\Modules\Schemas\Contracts\SchemaPropertyInterface;
use Sunchayn\Nimbus\Modules\Schemas\Enums\SchemaPropertyType;
use Sunchayn\Nimbus\Modules\Schemas\Enums\StringFormat;
use Sunchayn\Nimbus\Modules\Schemas\Services\RulesMapper\RuleToSchemaMapper;
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
                contentEncoding: $this->extractContentEncoding($field, $rules),
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
                // Items ($schemaProperty) will be set later by SchemaBuilder if needed.
                minItems: $schemaMetadata['minimum'],
                maxItems: $schemaMetadata['maximum'],
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
            if ($this->isFileRuleObject($rule)) {
                return StringFormat::BINARY;
            }

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

    /**
     * A base64 rule or a base64 looking name marks the string as base64 content.
     *
     * Custom rules are matched by name only, so the host app never needs to import anything from Nimbus.
     *
     * @param  NormalizedRulesShape  $rules
     */
    private function extractContentEncoding(string $field, array $rules): ?string
    {
        foreach ($rules as $rule) {
            $ruleName = is_object($rule)
                ? $rule::class
                : explode(':', (string) $rule)[0];

            if (str_contains(strtolower($ruleName), 'base64')) {
                return StringSchemaProperty::CONTENT_ENCODING_BASE64;
            }
        }

        $isBase64Name = preg_match('/(^|_)(base64|b64|data_?uri)$/', Str::snake($field)) === 1;

        return $isBase64Name ? StringSchemaProperty::CONTENT_ENCODING_BASE64 : null;
    }

    /**
     * The File rule object covers file(), image(), and the rules chained on them.
     */
    private function isFileRuleObject(mixed $rule): bool
    {
        return $rule instanceof File;
    }

    private function detectFormatFromRule(string $rule): ?StringFormat
    {
        // Rules such as mimes:jpg carry parameters that are not part of the name.
        return StringFormat::fromRule(strtolower(explode(':', $rule)[0]));
    }
}
