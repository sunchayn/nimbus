<?php

namespace Sunchayn\Nimbus\Modules\Schemas\Services\RulesMapper;

use Closure;
use Illuminate\Contracts\Validation\Rule as RuleContract;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Support\Str;
use Illuminate\Validation\ConditionalRules;
use Illuminate\Validation\Rules\Dimensions;
use Illuminate\Validation\Rules\Enum;
use Illuminate\Validation\Rules\Exists;
use Illuminate\Validation\Rules\File;
use Illuminate\Validation\Rules\In;
use Illuminate\Validation\Rules\ProhibitedIf;
use Illuminate\Validation\Rules\RequiredIf;
use Illuminate\Validation\Rules\Unique;
use Illuminate\Validation\ValidationRuleParser;
use ReflectionProperty;
use Sunchayn\Nimbus\Modules\Schemas\Enums\SchemaPropertyType;
use Sunchayn\Nimbus\Modules\Schemas\Services\RulesMapper\Processors\ConditionalRuleProcessor;
use Sunchayn\Nimbus\Modules\Schemas\Services\RulesMapper\Processors\EnumRuleProcessor;
use Sunchayn\Nimbus\Modules\Schemas\Services\RulesMapper\Processors\FormatRuleProcessor;
use Sunchayn\Nimbus\Modules\Schemas\Services\RulesMapper\Processors\InRuleProcessor;
use Sunchayn\Nimbus\Modules\Schemas\Services\RulesMapper\Processors\LookupRuleProcessor;
use Sunchayn\Nimbus\Modules\Schemas\Services\RulesMapper\Processors\RuleObjectProcessor;

/**
 * Converts Laravel validation rules into JSON Schema property definitions.
 *
 * @phpstan-import-type NormalizedRulesShape from \Sunchayn\Nimbus\Modules\Schemas\Collections\Ruleset
 *
 * @phpstan-type MappedShape array{
 *    type: SchemaPropertyType,
 *    required: bool,
 *    nullable: bool,
 *    format: string|null,
 *    enum: ?non-empty-array<array-key, scalar>,
 *    minimum: ?int,
 *    maximum: ?int,
 *    pattern: ?string,
 *    uniqueItems: bool,
 *    hints: string[],
 *    lookup: ?array{table: string, column: string},
 *    skipAutofill: bool,
 *    dateFormat: ?string,
 *  }
 * @phpstan-type RuleUpdates array{
 *    type?: SchemaPropertyType,
 *    required?: bool,
 *    nullable?: bool,
 *    sometimes?: bool,
 *    format?: string|null,
 *    enum?: ?non-empty-array<array-key, scalar>,
 *    minimum?: ?int,
 *    maximum?: ?int,
 *    pattern?: string,
 *    uniqueItems?: bool,
 *    hints?: string[],
 *    lookup?: array{table: string, column: string},
 *    skipAutofill?: bool,
 *    dateFormat?: string,
 *    digits?: array{int, int},
 *  }
 */
class RuleToSchemaMapper
{
    /**
     * Converts an array of Laravel validation rules into schema property data.
     *
     * @param  NormalizedRulesShape  $rules
     * @param  ?string  $field  The field name, used when a rule such as exists leaves out the column.
     * @return MappedShape
     */
    public function convertRulesToBaseSchemaPropertyMetadata(array $rules, ?string $field = null): array
    {
        $shape = [
            'type' => SchemaPropertyType::STRING,
            'required' => false,
            'nullable' => false,
            'sometimes' => false,
            'format' => null,
            'enum' => null,
            'minimum' => null,
            'maximum' => null,
            'pattern' => null,
            'uniqueItems' => false,
            'hints' => [],
            'lookup' => null,
            'skipAutofill' => false,
            'dateFormat' => null,
            'digits' => null,
        ];

        foreach ($rules as $rule) {
            $ruleSpecificUpdates = $this->processRule($rule, $field);

            $hints = [...$shape['hints'], ...($ruleSpecificUpdates['hints'] ?? [])];

            // Amend the original shape to add the changes specific to the rule in hand.
            $shape = array_merge($shape, $ruleSpecificUpdates, ['hints' => $hints]);
        }

        // A field that may be left out is never required, whatever else the rules say.
        $shape['required'] = $shape['required'] && ! $shape['sometimes'];

        $shape = array_merge($shape, $this->digitsUpdates($shape['type'], $shape['digits']));

        unset($shape['sometimes'], $shape['digits']);

        return $shape;
    }

    /**
     * Digits count characters, so the type decides between a value range and a text pattern.
     *
     * @param  ?array{int, int}  $digits
     * @return array{minimum?: int, maximum?: int, pattern?: string}
     */
    private function digitsUpdates(SchemaPropertyType $type, ?array $digits): array
    {
        if ($digits === null) {
            return [];
        }

        [$min, $max] = $digits;

        if (in_array($type, [SchemaPropertyType::INTEGER, SchemaPropertyType::NUMBER], true)) {
            return [
                'minimum' => $min > 1 ? 10 ** ($min - 1) : 0,
                'maximum' => 10 ** $max - 1,
            ];
        }

        return ['pattern' => $min === $max ? sprintf('^\d{%d}$', $min) : sprintf('^\d{%d,%d}$', $min, $max)];
    }

    /**
     * Processes individual validation rules and returns the changes to apply.
     *
     * @return RuleUpdates
     */
    private function processRule(mixed $rule, ?string $field): array
    {
        if (is_object($rule)) {
            return $this->processObjectRule($rule, $field);
        }

        if (! is_scalar($rule)) {
            return [];
        }

        [$name, $params] = ValidationRuleParser::parse((string) $rule);

        // The parser returns rule names in studly case, for example RequiredIf.
        $ruleName = Str::snake($name);

        $processed = array_merge(
            ConditionalRuleProcessor::process($ruleName, $params),
            FormatRuleProcessor::process($ruleName, $params),
            LookupRuleProcessor::process($ruleName, $params, $field),
        );

        return $processed + match ($ruleName) {
            'required' => ['required' => true],
            'string' => ['type' => SchemaPropertyType::STRING],
            'integer' => ['type' => SchemaPropertyType::INTEGER],
            'numeric' => ['type' => SchemaPropertyType::NUMBER],
            'boolean' => ['type' => SchemaPropertyType::BOOLEAN],
            'array' => ['type' => SchemaPropertyType::ARRAY],
            'json' => ['type' => SchemaPropertyType::OBJECT],
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
     * @return RuleUpdates
     */
    private function processObjectRule(object $rule, ?string $field): array
    {
        return match (true) {
            $rule instanceof Enum => EnumRuleProcessor::process($rule),
            $rule instanceof In => InRuleProcessor::process($rule),
            // These print as a plain rule string, for example "exists:users,id".
            $rule instanceof Exists, $rule instanceof Unique, $rule instanceof Dimensions => $this->processRule((string) $rule, $field),
            $rule instanceof RequiredIf => RuleObjectProcessor::requiredIf($rule),
            $rule instanceof ProhibitedIf => RuleObjectProcessor::prohibitedIf($rule),
            $rule instanceof ConditionalRules => $this->processConditionalRules($rule, $field),
            $rule instanceof Closure => RuleObjectProcessor::closure($rule),
            $rule instanceof File => [],
            $rule instanceof ValidationRule, $rule instanceof RuleContract => RuleObjectProcessor::custom($rule),
            default => [],
        };
    }

    /**
     * A rule set added with Rule::when() applies a fixed list, or an unknown one behind a closure.
     *
     * @return RuleUpdates
     */
    private function processConditionalRules(ConditionalRules $conditionalRules, ?string $field): array
    {
        $condition = (new ReflectionProperty($conditionalRules, 'condition'))->getValue($conditionalRules);
        $rules = (new ReflectionProperty($conditionalRules, 'rules'))->getValue($conditionalRules);
        $defaultRules = (new ReflectionProperty($conditionalRules, 'defaultRules'))->getValue($conditionalRules);

        $isKnown = is_bool($condition);

        $applied = $this->normalizeRules($isKnown && ! $condition ? $defaultRules : $rules);

        $updates = [];

        foreach ($applied as $rule) {
            $ruleUpdates = $this->processRule($rule, $field);

            $updates = array_merge(
                $updates,
                $ruleUpdates,
                [
                    'hints' => [
                        ...($updates['hints'] ?? []),
                        ...($ruleUpdates['hints'] ?? []),
                    ],
                ],
            );
        }

        if ($isKnown) {
            return $updates;
        }

        $closureHint = $condition instanceof Closure ? RuleObjectProcessor::describeClosure($condition) : 'with a condition';

        return array_merge($updates, [
            'sometimes' => true,
            'hints' => [...($updates['hints'] ?? []), 'rules apply conditionally, '.$closureHint],
        ]);
    }

    /**
     * @return array<array-key, mixed>
     */
    private function normalizeRules(mixed $rules): array
    {
        return match (true) {
            is_string($rules) => array_values(array_filter(explode('|', $rules))),
            is_array($rules) => $rules,
            default => [],
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
