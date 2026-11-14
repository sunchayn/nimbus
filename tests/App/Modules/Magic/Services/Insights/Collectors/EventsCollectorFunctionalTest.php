<?php

namespace Sunchayn\Nimbus\Tests\App\Modules\Magic\Services\Insights\Collectors;

use Generator;
use Illuminate\Support\Facades\Event;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use Sunchayn\Nimbus\Modules\Magic\Services\Insights\Collectors\EventsCollector;
use Sunchayn\Nimbus\Modules\Magic\Services\Insights\Support\InsightValueLimiter;
use Sunchayn\Nimbus\Tests\TestCase;

#[CoversClass(EventsCollector::class)]
class EventsCollectorFunctionalTest extends TestCase
{
    public function test_it_records_the_events_of_the_application(): void
    {
        // Arrange

        $collector = resolve(EventsCollector::class);
        $collector->start();

        // Act

        Event::dispatch('App\\Events\\OrderPlaced', [new \stdClass]);

        $result = $collector->collect();

        // Assert

        $this->assertSame([['name' => 'App\\Events\\OrderPlaced']], $result->entries);
    }

    #[DataProvider('frameworkEventsDataProvider')]
    public function test_it_leaves_out_events_of_the_framework_and_the_package(string $event): void
    {
        // Arrange

        $collector = resolve(EventsCollector::class);
        $collector->start();

        // Act

        Event::dispatch($event, [new \stdClass]);

        $result = $collector->collect();

        // Assert

        $this->assertSame([], $result->entries);
    }

    public static function frameworkEventsDataProvider(): Generator
    {
        yield 'illuminate' => ['event' => 'Illuminate\\Auth\\Events\\Login'];

        yield 'package' => ['event' => 'Sunchayn\\Nimbus\\Events\\Something'];

        yield 'laravel' => ['event' => 'Laravel\\Octane\\Events\\Tick'];

        yield 'carbon' => ['event' => 'Carbon\\Events\\Thing'];

        yield 'symfony' => ['event' => 'Symfony\\Component\\Events\\Thing'];
    }

    public function test_it_ignores_events_without_a_namespace_and_events_without_an_object_payload(): void
    {
        // Arrange

        $collector = resolve(EventsCollector::class);
        $collector->start();

        // Act

        Event::dispatch('eloquent', [new \stdClass]);
        Event::dispatch('App\\Events\\WithoutObject', ['text']);
        Event::dispatch('App\\Events\\WithoutPayload');

        $result = $collector->collect();

        // Assert

        $this->assertSame([], $result->entries);
    }

    public function test_it_does_not_record_the_events_other_collectors_own(): void
    {
        // Arrange

        $collector = resolve(EventsCollector::class);
        $collector->start();

        // Act

        cache()->put('key', 1);

        $result = $collector->collect();

        // Assert

        $this->assertSame([], $result->entries);
    }

    public function test_it_limits_the_length_of_an_event_name(): void
    {
        // Arrange

        $collector = resolve(EventsCollector::class);
        $collector->start();

        // Act

        Event::dispatch('App\\Events\\'.str_repeat('E', InsightValueLimiter::LIMIT + 50), [new \stdClass]);

        $result = $collector->collect();

        // Assert

        $this->assertSame(InsightValueLimiter::LIMIT, mb_strlen($result->entries[0]['name']));
    }

    public function test_it_caps_the_entries_and_counts_the_excess(): void
    {
        // Arrange

        $collector = resolve(EventsCollector::class);
        $collector->start();

        foreach (range(1, EventsCollector::MAX_ENTRIES + 1) as $i) {
            Event::dispatch('App\\Events\\Thing', [new \stdClass]);
        }

        // Act

        $result = $collector->collect();

        // Assert

        $this->assertCount(EventsCollector::MAX_ENTRIES, $result->entries);
        $this->assertSame(1, $result->dropped);
    }
}
