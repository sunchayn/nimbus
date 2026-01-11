<?php

namespace Sunchayn\Nimbus\Modules\Schemas\RulesMapper\Processors;

use BackedEnum;
use Illuminate\Validation\Rules\In;
use Sunchayn\Nimbus\Modules\Schemas\Enums\SchemaPropertyType;
use UnitEnum;

/**
 * Processes `In` validation rules to extract allowed values for schema generation.
 */
class InRuleProcessor
{
    /**
     * @return array{type: SchemaPropertyType::STRING | SchemaPropertyType::INTEGER, enum: ?non-empty-array<array-key, scalar>}
     */
    public static function process(In $in): array
    {
        /** @var array<array-key, scalar|object> $rawValues */
        $rawValues = invade($in)->values; // @phpstan-ignore-line

        // Normalize the values into primitives.
        $values = array_map(
            fn (bool|float|int|object|string $value): float|bool|int|string|null => match (true) {
                is_scalar($value) => $value,
                $value instanceof BackedEnum => $value->value,
                $value instanceof UnitEnum => $value->name,
                default => null,
            },
            $rawValues,
        );

        /** @var array<array-key, scalar>|array{} $values */
        $values = array_values(
            array_filter($values), // <- Removes null values.
        );

        if (empty($values)) {
            return ['type' => SchemaPropertyType::STRING, 'enum' => null];
        }

        $identityValue = $values[0];

        $type = match (true) {
            is_int($identityValue) => SchemaPropertyType::INTEGER,
            default => SchemaPropertyType::STRING,
        };

        return ['type' => $type, 'enum' => $values];
    }
}
