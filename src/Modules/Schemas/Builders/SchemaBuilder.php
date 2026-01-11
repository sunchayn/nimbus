<?php

namespace Sunchayn\Nimbus\Modules\Schemas\Builders;

use Illuminate\Support\Arr;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use Sunchayn\Nimbus\Modules\Routes\ValueObjects\RulesExtractionError;
use Sunchayn\Nimbus\Modules\Schemas\Collections\Ruleset;
use Sunchayn\Nimbus\Modules\Schemas\Contracts\SchemaPropertyInterface;
use Sunchayn\Nimbus\Modules\Schemas\Enums\RulesFieldType;
use Sunchayn\Nimbus\Modules\Schemas\ValueObjects\ArraySchemaProperty;
use Sunchayn\Nimbus\Modules\Schemas\ValueObjects\FieldPath;
use Sunchayn\Nimbus\Modules\Schemas\ValueObjects\ObjectSchemaProperty;
use Sunchayn\Nimbus\Modules\Schemas\ValueObjects\PathSegment;
use Sunchayn\Nimbus\Modules\Schemas\ValueObjects\Schema;

/**
 * Converts Laravel validation rules into JSON Schema structures.
 *
 * Handles three main patterns:
 * 1. Root fields: "name" => "required|string"
 * 2. Nested objects: "user.profile.name" => "required|string"
 * 3. Arrays: "tags.*" => "string" or "users.*.email" => "email"
 *
 * The builder processes rules in a specific order to ensure parent structures
 * exist before children are added.
 *
 * @phpstan-import-type NormalizedRulesShape from Ruleset
 */
class SchemaBuilder
{
    public function __construct(
        private readonly PropertyBuilder $propertyBuilder,
    ) {}

    public function buildSchemaFromRuleset(
        Ruleset $ruleset,
        ?RulesExtractionError $rulesExtractionError = null
    ): Schema {
        $properties = $this->buildProperties($ruleset);

        return new Schema(
            properties: $properties,
            extractionError: $rulesExtractionError,
        );
    }

    /**
     * @return array<string, SchemaPropertyInterface>
     */
    private function buildProperties(Ruleset $ruleset): array
    {
        return $this
            // Process in order: root fields → nested objects → arrays
            // This ensures parent structures exist before we add children
            ->sortRulesByProcessingOrder($ruleset)
            ->reduce(
                function (array $properties, array $rules, string $fieldName): array {
                    $fieldPath = FieldPath::fromString($fieldName);

                    if ($fieldPath->type === RulesFieldType::ARRAY_OF_PRIMITIVES) {
                        return $this->addSimpleArrayProperty($fieldPath, $rules, $properties);
                    }

                    if ($fieldPath->type === RulesFieldType::DOT_NOTATION) {
                        return $this->addDotNotationStructure($fieldPath, $rules, $properties);
                    }

                    // Builds a root-level property (e.g., "name", "email").
                    $properties[$fieldName] = $this->propertyBuilder->buildPropertyFromRules($fieldName, $rules);

                    return $properties;
                },
                initial: [],
            );
    }

    /**
     * Sorts rules by processing order: root → nested → wildcards.
     */
    private function sortRulesByProcessingOrder(Ruleset $ruleset): Ruleset
    {
        return $ruleset
            ->whereRootField()
            ->merge(
                $ruleset
                    ->whereDotNotationField()
                    // Sort nested fields by parent fields first,
                    // this way we make sure we create the parent schema first to keep things simple (relatively).
                    ->sortBy(fn (array $rules, string $field): int => strlen($field))
            )
            ->merge(
                $ruleset->whereArrayOfPrimitivesField(),
            );
    }

    /**
     * Adds a simple array property (e.g., "tags.*" => "string").
     *
     * @param  array<string, SchemaPropertyInterface>  $properties
     * @param  NormalizedRulesShape  $rules
     * @return array<string, SchemaPropertyInterface>
     */
    private function addSimpleArrayProperty(FieldPath $fieldPath, array $rules, array $properties): array
    {
        $arrayName = Str::replaceLast('.*', '', $fieldPath->value);

        // Get existing property to preserve 'required' status
        $existingProperty = $properties[$arrayName] ?? null;

        // Build the item schema (primitive type like string, integer, etc.)
        $schemaProperty = $this->propertyBuilder->buildPropertyFromRules(
            // Name the items after their array's name but in singular form.
            // It is an array of items, e.g. Tags -> each item is a tag.
            // Note: this also helps to make the payload generator more realistic in the FE.
            field: Str::singular($arrayName),
            rules: $rules,
        );

        $properties[$arrayName] = new ArraySchemaProperty(
            name: $arrayName,
            required: $existingProperty?->isRequired() ?? false,
            schemaProperty: $schemaProperty,
        );

        return $properties;
    }

    /**
     * Adds a dot notation structure (objects, arrays, or both).
     *
     * Handles both simple dot notation and complex array patterns:
     * - "user.profile.name" → nested objects
     * - "users.*.email" → array of objects with email property
     * - "company.teams.*.members.*.name" → deeply nested arrays
     *
     * @param  array<string, SchemaPropertyInterface>  $properties
     * @param  NormalizedRulesShape  $rules
     * @return array<string, SchemaPropertyInterface>
     */
    private function addDotNotationStructure(FieldPath $fieldPath, array $rules, array $properties): array
    {
        $rootField = $fieldPath->getRootField();

        $rootProperty = $properties[$rootField] ?? $this->createEmptyObject($rootField);

        $properties[$rootField] = $this->buildNestedStructure(
            $rootProperty,
            segments: $this->parsePathSegments($fieldPath->value),
            rules: $rules
        );

        return $properties;
    }

    /**
     * Parses a field path into typed segments.
     *
     * Example: "company.teams.*.members.*.name"
     * Returns:
     * [
     *   new PathSegment(value: 'teams'),
     *   new PathSegment(value: '*'),
     *   new PathSegment(value: 'members'),
     *   new PathSegment(value: '*'),
     *   new PathSegment(value: 'name', isLeaf: true),
     * ]
     *
     * @return array<array-key, PathSegment>
     */
    private function parsePathSegments(string $path): array
    {
        $parts = explode('.', $path);

        // Remove root field since we handle it separately.
        array_shift($parts);

        $leafIndex = count($parts) - 1;

        return Arr::map(
            $parts,
            fn (string $part, $index): PathSegment => new PathSegment(value: $part, isLeaf: $index === $leafIndex)
        );
    }

    /**
     * Recursively builds nested array/object structures.
     *
     * @param  NormalizedRulesShape  $rules
     * @param  PathSegment[]  $segments
     */
    private function buildNestedStructure(
        SchemaPropertyInterface $schemaProperty,
        array $segments,
        array $rules
    ): SchemaPropertyInterface {
        if ($segments === []) {
            return $schemaProperty;
        }

        $pathSegment = array_shift($segments);

        if ($pathSegment->isArray()) {
            return $this->convertPropertyToArray($schemaProperty, $segments, $rules);
        }

        return $this->addPropertyToStructure($schemaProperty, $pathSegment, $segments, $rules);
    }

    /**
     * Converts a property to an array type and processes remaining segments as array items.
     *
     * @param  NormalizedRulesShape  $rules
     * @param  PathSegment[]  $segments
     */
    private function convertPropertyToArray(
        SchemaPropertyInterface $schemaProperty,
        array $segments,
        array $rules
    ): SchemaPropertyInterface {
        // Get existing item schema if this is already an array, otherwise create new empty object
        $itemObject = ($schemaProperty instanceof ArraySchemaProperty)
            ? $schemaProperty->getItemsSchema() ?? $this->createEmptyObject(name: 'item')
            : $this->createEmptyObject(name: 'item');

        // Build the item structure from remaining segments.
        $itemSchema = $this->buildNestedStructure($itemObject, $segments, $rules);

        return new ArraySchemaProperty(
            name: $schemaProperty->getName(),
            required: $schemaProperty->isRequired(),
            schemaProperty: $itemSchema,
        );
    }

    /**
     * Adds a property to the current structure (object).
     *
     * @param  NormalizedRulesShape  $rules
     * @param  PathSegment[]  $remainingSegments
     */
    private function addPropertyToStructure(
        SchemaPropertyInterface $schemaProperty,
        PathSegment $pathSegment,
        array $remainingSegments,
        array $rules
    ): SchemaPropertyInterface {
        $propertyName = $pathSegment->value;

        // Get existing schema from object property
        $existingSchema = ($schemaProperty instanceof ObjectSchemaProperty)
            ? $schemaProperty->getPropertiesSchema() ?? new Schema([])
            : new Schema([]);

        /** @var Collection<string, SchemaPropertyInterface> $properties */
        $properties = Collection::make($existingSchema->properties)->keyBy(fn ($p): string => $p->getName());

        // If this is a leaf, build the final property with rules.
        if ($pathSegment->isLeaf) {
            $newProperty = $this->propertyBuilder->buildPropertyFromRules($propertyName, $rules);

            $properties->put($newProperty->getName(), $newProperty);

            return $this->rebuildObjectPropertyWithNewSchema($schemaProperty, $properties->values()->all());
        }

        // Otherwise, create/get intermediate object and recurse.
        $existingProperty = $properties->get($propertyName);
        $intermediateProperty = $existingProperty ?? $this->createEmptyObject($propertyName);

        $updatedProperty = $this->buildNestedStructure($intermediateProperty, $remainingSegments, $rules);

        $properties->put($updatedProperty->getName(), $updatedProperty);

        return $this->rebuildObjectPropertyWithNewSchema($schemaProperty, $properties->values()->all());
    }

    /**
     * Rebuilds a property with updated child properties.
     *
     * @param  SchemaPropertyInterface[]  $properties
     */
    private function rebuildObjectPropertyWithNewSchema(
        SchemaPropertyInterface $schemaProperty,
        array $properties
    ): SchemaPropertyInterface {
        return new ObjectSchemaProperty(
            name: $schemaProperty->getName(),
            required: $schemaProperty->isRequired(),
            schema: new Schema($properties),
        );
    }

    /**
     * Creates an empty object property.
     */
    private function createEmptyObject(string $name): SchemaPropertyInterface
    {
        return new ObjectSchemaProperty(
            name: $name,
            required: false,
            schema: new Schema([])
        );
    }
}
