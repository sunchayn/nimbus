<?php

namespace Sunchayn\Nimbus\Tests\App\Modules\Magic\Services\Insights\Support;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Sunchayn\Nimbus\Modules\Magic\Services\Insights\Support\InsightClock;

#[CoversClass(InsightClock::class)]
class InsightClockUnitTest extends TestCase
{
    public function test_it_measures_a_time_from_the_start_in_milliseconds(): void
    {
        // Arrange

        $clock = new InsightClock;
        $clock->start();

        // Act

        $offset = $clock->offsetMs(microtime(true) + 0.25);

        // Assert

        $this->assertGreaterThanOrEqual(250.0, $offset);
        $this->assertLessThan(260.0, $offset);
    }

    public function test_it_never_returns_a_negative_offset(): void
    {
        // Arrange

        $clock = new InsightClock;

        // Act & Assert

        $this->assertSame(0.0, $clock->offsetMs(0.0));
    }

    public function test_it_reports_the_time_elapsed_since_the_start(): void
    {
        // Arrange

        $clock = new InsightClock;
        $clock->start();

        // Act

        usleep(10_000);

        // Assert

        $this->assertGreaterThanOrEqual(10.0, $clock->elapsedMs());
    }

    public function test_it_rounds_the_offset_to_two_decimals(): void
    {
        // Arrange

        $clock = new InsightClock;

        // Act

        $offset = $clock->offsetMs(microtime(true) + 0.1234567);

        // Assert

        $this->assertSame(round($offset, 2), $offset);
    }
}
