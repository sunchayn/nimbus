<?php

declare(strict_types=1);

namespace Sunchayn\Nimbus\Modules\Config;

/**
 * Defines available random value generation strategies for global headers..
 */
enum GlobalHeaderGeneratorTypeEnum: string
{
    case Uuid = 'UUID';

    case Email = 'Email';

    case String = 'String';

    /**
     * Reconcile a raw value or case-insensitive string alias into a GlobalHeaderGeneratorTypeEnum instance.
     */
    public static function tryFromAlias(mixed $value): ?self
    {
        if ($value instanceof self) {
            return $value;
        }

        if (! is_string($value)) {
            return null;
        }

        return match (strtolower(trim($value))) {
            '$uuid' => self::Uuid,
            '$email' => self::Email,
            '$string' => self::String,
            default => null,
        };
    }
}
