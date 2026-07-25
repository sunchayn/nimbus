<?php

namespace Sunchayn\Nimbus\Modules\Schemas\Services\RulesMapper\Processors;

use BackedEnum;
use Illuminate\Validation\Rules\Enum;
use Sunchayn\Nimbus\Modules\Schemas\Enums\SchemaPropertyType;
use UnitEnum;

/**
 * Processes `Enum` validation rules to extract enum values for schema generation.
 */
class EnumRuleProcessor
{
    /**
     * @return array{type: SchemaPropertyType, enum: ?non-empty-array<array-key, scalar>}
     */
    public static function process(Enum $rule): array
    {
        /** @var class-string<UnitEnum> $enumClass */
        $enumClass = invade($rule)->type; // @phpstan-ignore-line

        if (! enum_exists($enumClass)) {
            return ['type' => SchemaPropertyType::STRING, 'enum' => null];
        }

        $values = array_map(
            fn (UnitEnum|BackedEnum $enum): int|string => $enum->value ?? $enum->name,
            $enumClass::cases()
        );

        if ($values === []) {
            return ['type' => SchemaPropertyType::STRING, 'enum' => null];
        }

        return ['type' => SchemaPropertyType::STRING, 'enum' => $values];
    }
}
