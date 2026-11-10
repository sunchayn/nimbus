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
 * - Content encoding (the string holds base64 bytes)
 */
class StringSchemaProperty implements SchemaPropertyInterface
{
    public const CONTENT_ENCODING_BASE64 = 'base64';

    /**
     * @param  array<array-key, scalar>|null  $enum
     */
    public function __construct(
        private readonly string $name,
        private readonly bool $required = false,
        private readonly bool $nullable = false,
        private readonly ?StringFormat $stringFormat = null,
        private readonly ?array $enum = null,
        private readonly ?int $minLength = null,
        private readonly ?int $maxLength = null,
        private readonly ?string $pattern = null,
        private readonly mixed $const = null,
        private readonly ?string $contentEncoding = null,
        private readonly PropertyAnnotations $propertyAnnotations = new PropertyAnnotations,
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

    public function getAnnotations(): PropertyAnnotations
    {
        return $this->propertyAnnotations;
    }

    public function getType(): SchemaPropertyType
    {
        return SchemaPropertyType::STRING;
    }

    /**
     * Whether the string is plain text, with no format, enum, constant, or encoding.
     */
    public function isUnconstrainedText(): bool
    {
        return ! $this->stringFormat instanceof \Sunchayn\Nimbus\Modules\Schemas\Enums\StringFormat
            && ($this->enum === null || $this->enum === [])
            && $this->const === null
            && $this->contentEncoding === null;
    }

    public function withContentEncoding(string $contentEncoding): self
    {
        return new self(
            name: $this->name,
            required: $this->required,
            nullable: $this->nullable,
            stringFormat: $this->stringFormat,
            enum: $this->enum,
            minLength: $this->minLength,
            maxLength: $this->maxLength,
            pattern: $this->pattern,
            const: $this->const,
            contentEncoding: $contentEncoding,
            propertyAnnotations: $this->propertyAnnotations,
        );
    }

    public function toJsonSchema(): array
    {
        $properties = [
            'type' => $this->nullable ? [$this->getType()->value, 'null'] : $this->getType()->value,
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

        if ($this->contentEncoding !== null) {
            $properties['contentEncoding'] = $this->contentEncoding;
        }

        return $properties + $this->propertyAnnotations->toJsonSchema();
    }
}
