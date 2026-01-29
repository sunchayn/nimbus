<?php

namespace Sunchayn\Nimbus\Modules\Schemas\ValueObjects;

use Sunchayn\Nimbus\Modules\Schemas\Contracts\SchemaPropertyInterface;
use Sunchayn\Nimbus\Modules\Schemas\Enums\SchemaPropertyType;

/**
 * Schema property for integer types.
 *
 * Supports JSON Schema integer validation including:
 * - Range constraints (minimum, maximum)
 * - Enum constraints
 */
class IntegerSchemaProperty implements SchemaPropertyInterface
{
    /**
     * @param  array<array-key, scalar>|null  $enum
     */
    public function __construct(
        private readonly string $name,
        private readonly bool $required = false,
        private readonly int|float|null $minimum = null,
        private readonly int|float|null $maximum = null,
        private readonly ?array $enum = null,
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
        return SchemaPropertyType::INTEGER;
    }

    public function toJsonSchema(): array
    {
        $properties = [
            'type' => $this->getType()->value,
        ];

        if ($this->minimum !== null) {
            $properties['minimum'] = $this->minimum;
        }

        if ($this->maximum !== null) {
            $properties['maximum'] = $this->maximum;
        }

        if ($this->enum !== null && $this->enum !== []) {
            $properties['enum'] = $this->enum;
        }

        return $properties;
    }
}
