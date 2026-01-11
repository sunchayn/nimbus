<?php

namespace Sunchayn\Nimbus\Modules\Schemas\Enums;

/**
 * Supported JSON Schema string formats.
 *
 * @see https://json-schema.org/draft/2020-12/json-schema-validation#section-7.3
 */
enum StringFormat: string
{
    case UUID = 'uuid';

    case EMAIL = 'email';

    case DATE_TIME = 'date-time';

    case URL = 'url';

    case URI = 'uri';

    case DATE = 'date';

    case TIME = 'time';

    /**
     * Map common validation rule names to their corresponding format.
     */
    public static function fromRule(string $rule): ?self
    {
        return match ($rule) {
            'uuid' => self::UUID,
            'email' => self::EMAIL,
            'date' => self::DATE_TIME, // Laravel's 'date' rule often maps to date-time in schema context
            'url' => self::URL,
            default => null,
        };
    }
}
