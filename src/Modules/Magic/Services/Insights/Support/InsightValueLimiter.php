<?php

declare(strict_types=1);

namespace Sunchayn\Nimbus\Modules\Magic\Services\Insights\Support;

use DateTimeInterface;
use Throwable;

/**
 * Turns any value into a short scalar so insight data stays small.
 */
class InsightValueLimiter
{
    public const LIMIT = 200;

    public function limit(mixed $value): mixed
    {
        return match (true) {
            $value === null || is_bool($value) || is_int($value) || is_float($value) => $value,
            is_string($value) => mb_substr($value, 0, self::LIMIT),
            $value instanceof DateTimeInterface => $value->format(DATE_ATOM),
            is_array($value) => mb_substr((string) json_encode($value, JSON_INVALID_UTF8_SUBSTITUTE | JSON_PARTIAL_OUTPUT_ON_ERROR), 0, self::LIMIT),
            is_object($value) => $value::class,
            default => get_debug_type($value),
        };
    }

    /**
     * @param  array<array-key, mixed>  $context
     * @return array<string, mixed>
     */
    public function limitContext(array $context): array
    {
        $limited = [];

        foreach (array_slice($context, 0, 20, true) as $key => $value) {
            $limited[(string) $key] = $value instanceof Throwable
                ? $value::class.': '.mb_substr($value->getMessage(), 0, self::LIMIT)
                : $this->limit($value);
        }

        return $limited;
    }
}
