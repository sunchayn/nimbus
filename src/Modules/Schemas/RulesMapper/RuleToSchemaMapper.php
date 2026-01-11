<?php

namespace Sunchayn\Nimbus\Modules\Schemas\RulesMapper;

use Illuminate\Validation\Rules\Enum;
use Illuminate\Validation\Rules\In;
use Illuminate\Validation\ValidationRuleParser;
use Sunchayn\Nimbus\Modules\Schemas\Enums\SchemaPropertyType;
use Sunchayn\Nimbus\Modules\Schemas\RulesMapper\Processors\EnumRuleProcessor;
use Sunchayn\Nimbus\Modules\Schemas\RulesMapper\Processors\InRuleProcessor;

/**
 * Converts Laravel validation rules into JSON Schema property definitions.
 *
 * @phpstan-import-type NormalizedRulesShape from \Sunchayn\Nimbus\Modules\Schemas\Collections\Ruleset
 */
class RuleToSchemaMapper
{
    /**
     * Converts an array of Laravel validation rules into schema property data.
     *
     * @param  NormalizedRulesShape  $rules
     * @return array{
     *    type: SchemaPropertyType,
     *    required: bool,
     *    format: string|null,
     *    enum: ?non-empty-array<array-key, scalar>,
     *    minimum: ?int,
     *    maximum: ?int,
     *  }
     */
    public function convertRulesToBaseSchemaPropertyMetadata(array $rules): array
    {
        $shape = [
            'type' => SchemaPropertyType::STRING,
            'required' => false,
            'format' => null,
            'enum' => null,
            'minimum' => null,
            'maximum' => null,
        ];

        foreach ($rules as $rule) {
            $ruleSpecificUpdates = $this->processRule($rule);

            // Amend the original shape to add the changes specific to the rule in hand.
            $shape = array_merge($shape, $ruleSpecificUpdates);
        }

        return $shape;
    }

    /**
     * Processes individual validation rules and returns the changes to apply.
     *
     * @return array{
     *     type?: SchemaPropertyType,
     *     format?: string,
     *     enum?: ?non-empty-array<array-key, scalar>,
     *     minimum?: int,
     *     maximum?: int,
     *     required?: bool,
     *     }|array{}
     */
    private function processRule(mixed $rule): array
    {
        if (is_object($rule)) {
            return $this->processObjectRule($rule);
        }

        if (! is_scalar($rule)) {
            return [];
        }

        [$name, $params] = ValidationRuleParser::parse((string) $rule);

        $ruleName = strtolower($name);

        return match ($ruleName) {
            'required' => ['required' => true],
            'string' => ['type' => SchemaPropertyType::STRING],
            'integer' => ['type' => SchemaPropertyType::INTEGER],
            'numeric' => ['type' => SchemaPropertyType::NUMBER],
            'boolean' => ['type' => SchemaPropertyType::BOOLEAN],
            'array' => ['type' => SchemaPropertyType::ARRAY],
            'email' => $this->setFormat('email'),
            'uuid' => $this->setFormat('uuid'),
            'date' => $this->setFormat('date-time'),
            'in' => $this->setEnum($params),
            'min' => ['minimum' => $params[0] ?? null],
            'max' => ['maximum' => $params[0] ?? null],
            'size' => ['minimum' => $params[0] ?? null, 'maximum' => $params[0] ?? null],
            default => [],
        };
    }

    /**
     * @return array{type: SchemaPropertyType, enum?: ?non-empty-array<array-key, scalar>}
     */
    private function processObjectRule(object $rule): array
    {
        return match (true) {
            $rule instanceof Enum => EnumRuleProcessor::process($rule),
            $rule instanceof In => InRuleProcessor::process($rule),
            default => ['type' => SchemaPropertyType::STRING],
        };
    }

    /**
     * Sets the format specification for the property.
     *
     * @return array{format: string, type?: SchemaPropertyType}
     */
    private function setFormat(string $format): array
    {
        $result = ['format' => $format];

        // Email, UUID, and date-time are all string-based formats in JSON Schema
        if (in_array($format, ['email', 'uuid', 'date-time'], true)) {
            $result['type'] = SchemaPropertyType::STRING;
        }

        return $result;
    }

    /**
     * @param  array<array-key, scalar>  $params
     * @return array{enum: non-empty-array<array-key, scalar>}|array{}
     */
    private function setEnum(array $params): array
    {
        if ($params === []) {
            return [];
        }

        return ['enum' => $params];
    }
}
