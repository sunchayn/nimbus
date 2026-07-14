<?php

declare(strict_types=1);

namespace Sunchayn\Nimbus\Modules\Schemas\ValueObjects;

use Sunchayn\Nimbus\Modules\Schemas\Contracts\SchemaPropertyInterface;
use Sunchayn\Nimbus\Modules\Schemas\Enums\SchemaPropertyType;

/**
 * Schema property for object types.
 *
 * Supports JSON Schema object validation including:
 * - Nested properties (defined via Schema)
 * - Required properties list
 * - Additional properties control
 *
 * Objects can contain any combination of property types,
 * including nested objects and arrays.
 */
class ObjectSchemaProperty implements SchemaPropertyInterface
{
    public function __construct(
        private readonly string $name,
        private readonly bool $required = false,
        private readonly ?Schema $schema = null,
        private readonly bool $additionalProperties = false,
    ) {}

    public function getName(): string
    {
        return $this->name;
    }

    public function isRequired(): bool
    {
        return $this->required;
    }

    /**
     * Get the properties schema (for nested structure building).
     */
    public function getPropertiesSchema(): ?Schema
    {
        return $this->schema;
    }

    public function getType(): SchemaPropertyType
    {
        return SchemaPropertyType::OBJECT;
    }

    public function toJsonSchema(): array
    {
        $result = [
            'type' => $this->getType()->value,
        ];

        if ($this->schema instanceof \Sunchayn\Nimbus\Modules\Schemas\ValueObjects\Schema && ! $this->schema->isEmpty()) {
            $result['properties'] = $this->schema->toPropertiesArray();
            $result['required'] = $this->schema->getRequiredProperties();
        }

        $result['additionalProperties'] = $this->additionalProperties;

        return $result;
    }
}
