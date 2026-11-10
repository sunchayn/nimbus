<?php

declare(strict_types=1);

namespace Sunchayn\Nimbus\Tests\App\Modules\Magic\Actions;

use Generator;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use Sunchayn\Nimbus\Modules\Magic\Actions\DetectEphemeralRateLimiterAction;
use Sunchayn\Nimbus\Tests\TestCase;

#[CoversClass(DetectEphemeralRateLimiterAction::class)]
class DetectEphemeralRateLimiterActionFunctionalTest extends TestCase
{
    #[DataProvider('defaultStoreProvider')]
    public function test_it_checks_the_default_cache_store(string $driver, bool $expected): void
    {
        // Arrange

        config()->set('cache.default', 'checked');
        config()->set('cache.limiter', null);
        config()->set('cache.stores.checked', ['driver' => $driver]);

        $action = resolve(DetectEphemeralRateLimiterAction::class);

        // Act

        $result = $action->execute();

        // Assert

        $this->assertSame($expected, $result);
    }

    public function test_it_prefers_the_limiter_store_over_the_default_store(): void
    {
        // Arrange

        config()->set('cache.default', 'array');
        config()->set('cache.limiter', 'persistent');
        config()->set('cache.stores.persistent', ['driver' => 'file']);

        $action = resolve(DetectEphemeralRateLimiterAction::class);

        // Act

        $result = $action->execute();

        // Assert

        $this->assertFalse($result);
    }

    public static function defaultStoreProvider(): Generator
    {
        yield 'array driver' => [
            'driver' => 'array',
            'expected' => true,
        ];

        yield 'null driver' => [
            'driver' => 'null',
            'expected' => true,
        ];

        yield 'file driver' => [
            'driver' => 'file',
            'expected' => false,
        ];

        yield 'redis driver' => [
            'driver' => 'redis',
            'expected' => false,
        ];
    }
}
