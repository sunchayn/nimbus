<?php

namespace Sunchayn\Nimbus\Modules\Schemas\ValueObjects;

use Illuminate\Contracts\Support\Arrayable;
use Illuminate\Support\Arr;
use Sunchayn\Nimbus\Modules\Routes\ValueObjects\RulesExtractionError;
use Sunchayn\Nimbus\Modules\Schemas\Contracts\SchemaPropertyInterface;

/**
 * @phpstan-type SchemaShape array{
 *      '$schema': 'https://json-schema.org/draft/2020-12/schema',
 *      type: 'object',
 *      properties: array<string, array<string, mixed>>,
 *      required: string[],
 *      additionalProperties: false,
 *  }
 *
 * @implements Arrayable<string, mixed>
 */
class Schema implements Arrayable
{
    /** @var SchemaPropertyInterface[] */
    public readonly array $properties;

    /**
     * @param  SchemaPropertyInterface[]  $properties
     */
    public function __construct(
        array $properties,
        public readonly ?RulesExtractionError $extractionError = null,
    ) {
        $this->properties = array_values($properties);
    }

    public static function empty(): self
    {
        return new self(
            properties: [],
        );
    }

    public function isEmpty(): bool
    {
        return $this->properties === [];
    }

    /**
     * @return string[]
     */
    public function getRequiredProperties(): array
    {
        return collect($this->properties)
            ->filter(fn (SchemaPropertyInterface $schemaProperty): bool => $schemaProperty->isRequired())
            ->map(fn (SchemaPropertyInterface $schemaProperty): string => $schemaProperty->getName())
            ->values()
            ->all();
    }

    /**
     * Convert properties to JSON Schema format (for nested objects).
     *
     * This method is used when serializing object properties to JSON Schema.
     * It produces a map of property names to their JSON Schema representations.
     *
     * @return array<string, array<string, mixed>>
     */
    public function toPropertiesArray(): array
    {
        return Arr::mapWithKeys(
            $this->properties,
            fn (SchemaPropertyInterface $schemaProperty): array => [
                $schemaProperty->getName() => $schemaProperty->toJsonSchema(),
            ]
        );
    }

    public function toArray(): array
    {
        return $this->toJsonSchema();
    }

    /**
     * Converts this schema to proper JSON Schema format.
     *
     * Generates a complete JSON Schema object with all necessary metadata
     * that can be used directly by JSON Schema validators and editors.
     *
     * @return array<string, mixed>
     */
    public function toJsonSchema(): array
    {
        return [
            '$schema' => 'https://json-schema.org/draft/2020-12/schema',
            'type' => 'object',
            'properties' => $this->toPropertiesArray(),
            'required' => $this->getRequiredProperties(),
            'additionalProperties' => false,
        ];
    }
}
