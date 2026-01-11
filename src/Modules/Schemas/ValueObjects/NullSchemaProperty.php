<?php

namespace Sunchayn\Nimbus\Modules\Schemas\ValueObjects;

use Sunchayn\Nimbus\Modules\Schemas\Contracts\SchemaPropertyInterface;
use Sunchayn\Nimbus\Modules\Schemas\Enums\SchemaPropertyType;

/**
 * Schema property for null type.
 *
 * Represents a JSON Schema null type, typically used in combination
 * with other types (e.g., ["string", "null"] for nullable strings).
 */
class NullSchemaProperty implements SchemaPropertyInterface
{
    public function __construct(
        private readonly string $name,
        private readonly bool $required = false,
    ) {}

    public function getName(): string
    {
        return $this->name;
    }

    public function isRequired(): bool
    {
        return $this->required;
    }

    public function getType(): SchemaPropertyType
    {
        return SchemaPropertyType::NULL;
    }

    public function toJsonSchema(): array
    {
        return [
            'type' => $this->getType()->value,
        ];
    }
}
