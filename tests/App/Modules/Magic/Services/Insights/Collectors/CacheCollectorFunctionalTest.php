<?php

namespace Sunchayn\Nimbus\Tests\App\Modules\Magic\Services\Insights\Collectors;

use Illuminate\Support\Facades\Cache;
use PHPUnit\Framework\Attributes\CoversClass;
use Sunchayn\Nimbus\Modules\Magic\Services\Insights\Collectors\CacheCollector;
use Sunchayn\Nimbus\Modules\Magic\Services\Insights\Collectors\ListCollector;
use Sunchayn\Nimbus\Modules\Magic\Services\Insights\Support\InsightValueLimiter;
use Sunchayn\Nimbus\Tests\TestCase;

#[CoversClass(CacheCollector::class)]
#[CoversClass(ListCollector::class)]
class CacheCollectorFunctionalTest extends TestCase
{
    public function test_it_records_each_cache_operation_with_its_key(): void
    {
        // Arrange

        $collector = resolve(CacheCollector::class);
        $collector->start();

        // Act

        Cache::get('missing');
        Cache::put('stored', 1);
        Cache::get('stored');
        Cache::forget('stored');

        $result = $collector->collect();

        // Assert

        $this->assertSame(
            [
                ['operation' => 'miss', 'key' => 'missing'],
                ['operation' => 'write', 'key' => 'stored'],
                ['operation' => 'hit', 'key' => 'stored'],
                ['operation' => 'forget', 'key' => 'stored'],
            ],
            $result->entries,
        );
    }

    public function test_it_limits_the_length_of_a_key(): void
    {
        // Arrange

        $collector = resolve(CacheCollector::class);
        $collector->start();

        // Act

        Cache::get(str_repeat('k', InsightValueLimiter::LIMIT + 50));

        $result = $collector->collect();

        // Assert

        $this->assertSame(str_repeat('k', InsightValueLimiter::LIMIT), $result->entries[0]['key']);
    }

    public function test_it_caps_the_entries_and_counts_the_excess(): void
    {
        // Arrange

        $collector = resolve(CacheCollector::class);
        $collector->start();

        foreach (range(1, CacheCollector::MAX_ENTRIES + 3) as $i) {
            Cache::get('key-'.$i);
        }

        // Act

        $result = $collector->collect();

        // Assert

        $this->assertCount(CacheCollector::MAX_ENTRIES, $result->entries);
        $this->assertSame(3, $result->dropped);
    }

    public function test_it_records_nothing_before_it_starts(): void
    {
        // Arrange

        $collector = resolve(CacheCollector::class);

        // Act

        Cache::get('early');

        // Assert

        $this->assertSame([], $collector->collect()->entries);
    }
}
