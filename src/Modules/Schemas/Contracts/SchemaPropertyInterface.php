<?php

declare(strict_types=1);

namespace Sunchayn\Nimbus\Modules\Schemas\Contracts;

use Sunchayn\Nimbus\Modules\Schemas\Enums\SchemaPropertyType;

/**
 * Interface for all schema property types.
 *
 * Defines the contract that all schema properties must implement,
 * ensuring consistent serialization to both internal format (with custom properties)
 * and standard JSON Schema format.
 */
interface SchemaPropertyInterface
{
    /**
     * Get the property name.
     */
    public function getName(): string;

    /**
     * Check if the property is required.
     */
    public function isRequired(): bool;

    /**
     * Get the JSON Schema type.
     */
    public function getType(): SchemaPropertyType;

    /**
     * Convert to standard JSON Schema format.
     *
     * This produces a pure JSON Schema Draft 2020-12 compliant structure
     * without custom extensions.
     *
     * @return array<string, mixed>
     */
    public function toJsonSchema(): array;
}
