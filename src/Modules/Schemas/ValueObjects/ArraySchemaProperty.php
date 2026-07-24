<?php

declare(strict_types=1);

namespace Sunchayn\Nimbus\Modules\Schemas\ValueObjects;

use Sunchayn\Nimbus\Modules\Schemas\Contracts\SchemaPropertyInterface;
use Sunchayn\Nimbus\Modules\Schemas\Enums\SchemaPropertyType;

/**
 * Schema property for array types.
 *
 * Supports JSON Schema array validation including:
 * - Item schema definition (can be any property type)
 * - Size constraints (minItems, maxItems)
 *
 * Arrays can contain:
 * - Primitives (string, integer, boolean)
 * - Objects
 * - Nested arrays
 */
class ArraySchemaProperty implements SchemaPropertyInterface
{
    public function __construct(
        private readonly string $name,
        private readonly bool $required = false,
        private readonly bool $nullable = false,
        private readonly ?SchemaPropertyInterface $schemaProperty = null,
        private readonly ?int $minItems = null,
        private readonly ?int $maxItems = null,
    ) {}

    public function getName(): string
    {
        return $this->name;
    }

    public function isRequired(): bool
    {
        return $this->required;
    }

    public function isNullable(): bool
    {
        return $this->nullable;
    }

    /**
     * Get the items schema (for nested structure building).
     */
    public function getItemsSchema(): ?SchemaPropertyInterface
    {
        return $this->schemaProperty;
    }

    public function getType(): SchemaPropertyType
    {
        return SchemaPropertyType::ARRAY;
    }

    public function toJsonSchema(): array
    {
        $properties = [
            'type' => $this->nullable ? [$this->getType()->value, 'null'] : $this->getType()->value,
        ];

        if ($this->schemaProperty instanceof \Sunchayn\Nimbus\Modules\Schemas\Contracts\SchemaPropertyInterface) {
            $properties['items'] = $this->schemaProperty->toJsonSchema();
        }

        if ($this->minItems !== null) {
            $properties['minItems'] = $this->minItems;
        }

        if ($this->maxItems !== null) {
            $properties['maxItems'] = $this->maxItems;
        }

        return $properties;
    }
}
