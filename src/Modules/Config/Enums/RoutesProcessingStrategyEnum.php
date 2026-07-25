<?php

declare(strict_types=1);

namespace Sunchayn\Nimbus\Modules\Config\Enums;

enum RoutesProcessingStrategyEnum: string
{
    case OpenAPI = 'OpenAPI Specification';

    case AutoDetect = 'Laravel Routes';

    /**
     * Reconcile a raw config value or string alias into a RoutesProcessingStrategyEnum instance.
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
            'auto_detect' => self::AutoDetect,
            'openapi' => self::OpenAPI,
            default => null,
        };
    }
}
