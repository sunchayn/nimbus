<?php

declare(strict_types=1);

namespace Sunchayn\Nimbus\Modules\Schemas\Enums;

/**
 * JSON Schema property types.
 *
 * Represents the standard JSON Schema primitive and complex types.
 *
 * @see https://json-schema.org/understanding-json-schema/reference/type
 */
enum SchemaPropertyType: string
{
    case STRING = 'string';

    case INTEGER = 'integer';

    case NUMBER = 'number';

    case BOOLEAN = 'boolean';

    case ARRAY = 'array';

    case OBJECT = 'object';

    public function fromPhpScalar(string $phpScalarType): ?self
    {
        return match (true) {
            $phpScalarType === 'string' => self::STRING,
            $phpScalarType === 'int' => self::INTEGER,
            $phpScalarType === 'float' => self::NUMBER,
            $phpScalarType === 'bool' => self::BOOLEAN,
            default => null,
        };
    }
}
