<?php

declare(strict_types=1);

namespace Sunchayn\Nimbus\Modules\Magic\Listeners;

use Carbon\Carbon;
use Carbon\CarbonImmutable;
use Illuminate\Routing\Events\RouteMatched;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * Fixes the clock of the request to the time in the `X-Nimbus-Time-Travel` header.
 *
 * The value is a date time or a relative form such as `+31 days`.
 * A value that cannot be parsed is ignored and logged at debug level.
 */
class TimeTravelListener
{
    public const HEADER = 'X-Nimbus-Time-Travel';

    public function handle(RouteMatched $routeMatched): void
    {
        $value = $routeMatched->request->header(self::HEADER);

        if (! is_string($value) || trim($value) === '') {
            return;
        }

        try {
            $moment = Carbon::parse(trim($value));
        } catch (Throwable $throwable) {
            Log::debug('Nimbus ignored the time travel header.', ['reason' => $throwable->getMessage()]);

            return;
        }

        Carbon::setTestNow($moment);
        CarbonImmutable::setTestNow($moment);

        // The clock is restored so a long running worker does not keep the fake time.
        app()->terminating(function (): void {
            Carbon::setTestNow();
            CarbonImmutable::setTestNow();
        });
    }
}
