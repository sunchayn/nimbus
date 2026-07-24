<?php

declare(strict_types=1);

namespace Sunchayn\Nimbus\Modules\Schemas\ValueObjects;

use Sunchayn\Nimbus\Modules\Schemas\Contracts\SchemaPropertyInterface;
use Sunchayn\Nimbus\Modules\Schemas\Enums\SchemaPropertyType;

/**
 * Schema property for boolean types.
 *
 * Simple boolean type with no additional constraints.
 */
class BooleanSchemaProperty implements SchemaPropertyInterface
{
    public function __construct(
        private readonly string $name,
        private readonly bool $required = false,
        private readonly bool $nullable = false,
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

    public function getType(): SchemaPropertyType
    {
        return SchemaPropertyType::BOOLEAN;
    }

    public function toJsonSchema(): array
    {
        return [
            'type' => $this->nullable ? [$this->getType()->value, 'null'] : $this->getType()->value,
        ];
    }
}
