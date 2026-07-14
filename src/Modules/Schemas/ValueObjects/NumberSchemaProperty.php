<?php

declare(strict_types=1);

namespace Sunchayn\Nimbus\Modules\Schemas\ValueObjects;

use Sunchayn\Nimbus\Modules\Schemas\Contracts\SchemaPropertyInterface;
use Sunchayn\Nimbus\Modules\Schemas\Enums\SchemaPropertyType;

/**
 * Schema property for number types (floating point).
 *
 * Supports JSON Schema number validation including:
 * - Range constraints (minimum, maximum)
 */
class NumberSchemaProperty implements SchemaPropertyInterface
{
    public function __construct(
        private readonly string $name,
        private readonly bool $required = false,
        private readonly ?float $minimum = null,
        private readonly ?float $maximum = null,
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
        return SchemaPropertyType::NUMBER;
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

        return $properties;
    }
}
