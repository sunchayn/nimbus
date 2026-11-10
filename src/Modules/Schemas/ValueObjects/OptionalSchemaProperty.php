<?php

declare(strict_types=1);

namespace Sunchayn\Nimbus\Modules\Schemas\ValueObjects;

use Sunchayn\Nimbus\Modules\Schemas\Contracts\SchemaPropertyInterface;
use Sunchayn\Nimbus\Modules\Schemas\Enums\SchemaPropertyType;

/**
 * Wraps a property that may be missing from a payload, such as a resource key behind `when()`.
 */
class OptionalSchemaProperty implements SchemaPropertyInterface
{
    public function __construct(
        private readonly SchemaPropertyInterface $schemaProperty,
    ) {}

    public function getName(): string
    {
        return $this->schemaProperty->getName();
    }

    public function isRequired(): bool
    {
        return false;
    }

    public function getType(): SchemaPropertyType
    {
        return $this->schemaProperty->getType();
    }

    public function isNullable(): bool
    {
        return $this->schemaProperty->isNullable();
    }

    public function getAnnotations(): PropertyAnnotations
    {
        return $this->schemaProperty->getAnnotations();
    }

    public function toJsonSchema(): array
    {
        return $this->schemaProperty->toJsonSchema();
    }
}
