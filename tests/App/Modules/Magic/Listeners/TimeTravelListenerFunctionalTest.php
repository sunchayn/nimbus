<?php

namespace Sunchayn\Nimbus\Tests\App\Modules\Magic\Listeners;

use Carbon\Carbon;
use Carbon\CarbonImmutable;
use Illuminate\Http\Request;
use Illuminate\Routing\Events\RouteMatched;
use Illuminate\Routing\Route;
use PHPUnit\Framework\Attributes\CoversClass;
use Sunchayn\Nimbus\Modules\Magic\Listeners\TimeTravelListener;
use Sunchayn\Nimbus\Tests\TestCase;

#[CoversClass(TimeTravelListener::class)]
class TimeTravelListenerFunctionalTest extends TestCase
{
    protected function tearDown(): void
    {
        Carbon::setTestNow();
        CarbonImmutable::setTestNow();

        parent::tearDown();
    }

    public function test_it_fixes_the_clock_to_a_date_time(): void
    {
        // Act

        $this->dispatchWithHeader('2030-05-06 07:08:09');

        // Assert

        $this->assertSame('2030-05-06 07:08:09', Carbon::now()->format('Y-m-d H:i:s'));
        $this->assertSame('2030-05-06 07:08:09', CarbonImmutable::now()->format('Y-m-d H:i:s'));
    }

    public function test_it_accepts_a_relative_form(): void
    {
        // Arrange

        $expected = Carbon::now()->addDays(31);

        // Act

        $this->dispatchWithHeader('+31 days');

        // Assert

        $this->assertEqualsWithDelta($expected->getTimestamp(), Carbon::now()->getTimestamp(), 5);
    }

    public function test_it_restores_the_clock_on_termination(): void
    {
        // Arrange

        $this->dispatchWithHeader('2030-05-06 07:08:09');

        // Act

        $this->app->terminate();

        // Assert

        $this->assertFalse(Carbon::hasTestNow());
        $this->assertFalse(CarbonImmutable::hasTestNow());
    }

    public function test_it_ignores_a_value_that_cannot_be_parsed(): void
    {
        // Act

        $this->dispatchWithHeader('not a date at all');

        // Assert

        $this->assertFalse(Carbon::hasTestNow());
    }

    public function test_it_does_nothing_without_the_header(): void
    {
        // Act

        $this->dispatchWithHeader(null);

        // Assert

        $this->assertFalse(Carbon::hasTestNow());
    }

    private function dispatchWithHeader(?string $value): void
    {
        $request = Request::create('/test', 'GET');

        if ($value !== null) {
            $request->headers->set(TimeTravelListener::HEADER, $value);
        }

        $this->app['events']->dispatch(new RouteMatched(new Route(['GET'], '/test', fn () => 'ok'), $request));
    }
}
