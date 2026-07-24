<?php

declare(strict_types=1);

namespace Sunchayn\Nimbus\Modules\Schemas\Collections;

use Illuminate\Support\Collection;
use Illuminate\Validation\Rule;
use InvalidArgumentException;
use Sunchayn\Nimbus\Modules\Schemas\Enums\RulesFieldType;
use Sunchayn\Nimbus\Modules\Schemas\ValueObjects\FieldPath;

/**
 * Represents a normalized set of Laravel validation rules.
 *
 * Handles conversion from various input formats (string, array) to a consistent
 * array format for processing throughout the schema building pipeline.
 *
 * @example
 * Input:  "required|string|max:255"
 * Output: ["required", "string", "max:255"]
 *
 * @phpstan-type NormalizedRulesShape array<array-key, string|Rule>
 *
 * @extends  Collection<string, NormalizedRulesShape>
 */
class Ruleset extends Collection
{
    public function __construct($items = [])
    {
        parent::__construct($items);

        if (! $this->every(fn (mixed $item): bool => is_array($item))) { // @phpstan-ignore-line this is a runtime check.
            throw new InvalidArgumentException('Ruleset items must be an array');
        }
    }

    /**
     * Creates a Ruleset from various input formats.
     *
     * Handles both string format ("required|string|max:255") and array format
     * to ensure consistent processing throughout the schema builder.
     *
     * @param  array<string, mixed>  $rules
     */
    public static function fromLaravelRules(array $rules): self
    {
        $normalized = array_map(
            function (mixed $fieldRules): array {
                // Convert pipe-separated string to array
                if (is_string($fieldRules)) {
                    return array_values(
                        array_filter(explode('|', $fieldRules)),
                    );
                }

                // Return array as-is
                if (is_array($fieldRules)) {
                    return $fieldRules;
                }

                // Wrap objets in arrays, these can be Rules.
                if (is_object($fieldRules)) {
                    return [$fieldRules];
                }

                // If unknown, return an empty array.
                return [];
            },
            $rules,
        );

        return new self($normalized);
    }

    public function whereRootField(): self
    {
        return $this->where(fn (mixed $_, string $field): bool => FieldPath::fromString($field)->type === RulesFieldType::ROOT);
    }

    public function whereDotNotationField(): self
    {
        return $this->where(function (mixed $_, string $field): bool {
            $fieldPath = FieldPath::fromString($field);

            return $fieldPath->type === RulesFieldType::DOT_NOTATION;
        });
    }

    public function whereArrayOfPrimitivesField(): self
    {
        return $this->where(fn (mixed $_, string $field): bool => FieldPath::fromString($field)->type === RulesFieldType::ARRAY_OF_PRIMITIVES);
    }
}
