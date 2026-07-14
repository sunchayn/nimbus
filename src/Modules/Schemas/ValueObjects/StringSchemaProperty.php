<?php

declare(strict_types=1);

namespace Sunchayn\Nimbus\Modules\Schemas\ValueObjects;

use Sunchayn\Nimbus\Modules\Schemas\Contracts\SchemaPropertyInterface;
use Sunchayn\Nimbus\Modules\Schemas\Enums\SchemaPropertyType;
use Sunchayn\Nimbus\Modules\Schemas\Enums\StringFormat;

/**
 * Schema property for string types.
 *
 * Supports JSON Schema string validation including:
 * - Format validation (email, uuid, date-time, etc.)
 * - Enum constraints
 * - Length constraints (minLength, maxLength)
 */
class StringSchemaProperty implements SchemaPropertyInterface
{
    /**
     * @param  array<array-key, scalar>|null  $enum
     */
    public function __construct(
        private readonly string $name,
        private readonly bool $required = false,
        private readonly ?StringFormat $stringFormat = null,
        private readonly ?array $enum = null,
        private readonly ?int $minLength = null,
        private readonly ?int $maxLength = null,
        private readonly ?string $pattern = null,
        private readonly mixed $const = null,
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
        return SchemaPropertyType::STRING;
    }

    public function toJsonSchema(): array
    {
        $properties = [
            'type' => $this->getType()->value,
        ];

        if ($this->stringFormat instanceof \Sunchayn\Nimbus\Modules\Schemas\Enums\StringFormat) {
            $properties['format'] = $this->stringFormat->value;
        }

        if ($this->enum !== null && $this->enum !== []) {
            $properties['enum'] = $this->enum;
        }

        if ($this->minLength !== null) {
            $properties['minLength'] = $this->minLength;
        }

        if ($this->maxLength !== null) {
            $properties['maxLength'] = $this->maxLength;
        }

        if ($this->pattern !== null) {
            $properties['pattern'] = $this->pattern;
        }

        if ($this->const !== null) {
            $properties['const'] = $this->const;
        }

        return $properties;
    }
}
