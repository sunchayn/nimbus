<?php

namespace Sunchayn\Nimbus\Tests\App\Modules\Magic\Services\Insights\Collectors;

use ArrayObject;
use Illuminate\Notifications\Events\NotificationSending;
use PHPUnit\Framework\Attributes\CoversClass;
use Sunchayn\Nimbus\Modules\Magic\Services\Insights\Collectors\NotificationsCollector;
use Sunchayn\Nimbus\Tests\TestCase;

#[CoversClass(NotificationsCollector::class)]
class NotificationsCollectorFunctionalTest extends TestCase
{
    public function test_it_records_the_notification_class_and_its_channel(): void
    {
        // Arrange

        $collector = resolve(NotificationsCollector::class);
        $collector->start();

        // Act

        event(new NotificationSending(new ArrayObject, new ArrayObject, 'mail'));

        $result = $collector->collect();

        // Assert

        $this->assertSame([['notification' => ArrayObject::class, 'channel' => 'mail']], $result->entries);
    }

    public function test_it_caps_the_entries_and_counts_the_excess(): void
    {
        // Arrange

        $collector = resolve(NotificationsCollector::class);
        $collector->start();

        foreach (range(1, NotificationsCollector::MAX_ENTRIES + 4) as $i) {
            event(new NotificationSending(new ArrayObject, new ArrayObject, 'database'));
        }

        // Act

        $result = $collector->collect();

        // Assert

        $this->assertCount(NotificationsCollector::MAX_ENTRIES, $result->entries);
        $this->assertSame(4, $result->dropped);
    }
}
