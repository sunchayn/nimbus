<?php

declare(strict_types=1);

namespace Sunchayn\Nimbus\Modules\Magic\Services\Insights\Support;

/**
 * Measures time from the start of the request, so every collector reports offsets on the same clock.
 */
class InsightClock
{
    private float $startedAt;

    public function __construct()
    {
        $this->startedAt = microtime(true);
    }

    /**
     * The framework start time is used when it is known, so the offsets include the boot of the application.
     */
    public function start(): void
    {
        $this->startedAt = defined('LARAVEL_START')
            ? (float) LARAVEL_START
            : microtime(true);
    }

    public function elapsedMs(): float
    {
        return $this->offsetMs(microtime(true));
    }

    /**
     * @param  float  $timestamp  A time in seconds, as `microtime(true)` returns it.
     */
    public function offsetMs(float $timestamp): float
    {
        return round(max(0.0, $timestamp - $this->startedAt) * 1000, 2);
    }
}
