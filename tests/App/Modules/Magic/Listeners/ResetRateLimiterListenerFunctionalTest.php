<?php

declare(strict_types=1);

namespace Sunchayn\Nimbus\Tests\App\Modules\Magic\Listeners;

use Illuminate\Support\Facades\Route;
use PHPUnit\Framework\Attributes\CoversClass;
use Sunchayn\Nimbus\Modules\Magic\Listeners\ResetRateLimiterListener;
use Sunchayn\Nimbus\Modules\Magic\Middleware\ResettingThrottleRequests;
use Sunchayn\Nimbus\Tests\TestCase;

#[CoversClass(ResetRateLimiterListener::class)]
#[CoversClass(ResettingThrottleRequests::class)]
class ResetRateLimiterListenerFunctionalTest extends TestCase
{
    public function test_it_clears_the_limiter_of_the_route_when_the_header_is_set(): void
    {
        // Arrange

        Route::get('/_limited', fn (): string => 'ok')->middleware('throttle:2,1')->name('limited');

        $this->get(route('limited'))->assertOk();
        $this->get(route('limited'))->assertOk();
        $this->get(route('limited'))->assertStatus(429);

        // Act

        $response = $this->get(route('limited'), ['X-Nimbus-Reset-Limiter' => '1']);

        // Assert

        $response->assertOk();
        $response->assertHeader('X-RateLimit-Remaining', '1');
    }

    public function test_it_keeps_the_limiter_without_the_header(): void
    {
        // Arrange

        Route::get('/_limited', fn (): string => 'ok')->middleware('throttle:1,1')->name('limited');

        $this->get(route('limited'))->assertOk();

        // Act

        $response = $this->get(route('limited'));

        // Assert

        $response->assertStatus(429);
    }

    public function test_it_counts_the_remaining_attempts_down_until_the_limit_is_reached(): void
    {
        // Arrange

        Route::get('/_limited', fn (): string => 'ok')->middleware('throttle:3,1')->name('limited');

        // Act

        $first = $this->get(route('limited'));
        $second = $this->get(route('limited'));
        $third = $this->get(route('limited'));
        $fourth = $this->get(route('limited'));

        // Assert

        $first->assertHeader('X-RateLimit-Remaining', '2');
        $second->assertHeader('X-RateLimit-Remaining', '1');
        $third->assertHeader('X-RateLimit-Remaining', '0');
        $fourth->assertStatus(429);
        $fourth->assertHeader('X-RateLimit-Remaining', '0');
    }

    public function test_it_starts_counting_from_the_full_limit_again_after_a_reset(): void
    {
        // Arrange

        Route::get('/_limited', fn (): string => 'ok')->middleware('throttle:3,1')->name('limited');

        $this->get(route('limited'));
        $this->get(route('limited'));

        // Act

        $response = $this->get(route('limited'), ['X-Nimbus-Reset-Limiter' => '1']);

        // Assert

        $response->assertOk();
        $response->assertHeader('X-RateLimit-Remaining', '2');
    }
}
