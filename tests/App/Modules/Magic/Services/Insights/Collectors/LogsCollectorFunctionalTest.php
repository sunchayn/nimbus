<?php

namespace Sunchayn\Nimbus\Tests\App\Modules\Magic\Services\Insights\Collectors;

use Illuminate\Log\Events\MessageLogged;
use Illuminate\Support\Facades\Log;
use PHPUnit\Framework\Attributes\CoversClass;
use Sunchayn\Nimbus\Modules\Magic\Services\Insights\Collectors\LogsCollector;
use Sunchayn\Nimbus\Tests\TestCase;

#[CoversClass(LogsCollector::class)]
class LogsCollectorFunctionalTest extends TestCase
{
    public function test_it_collects_the_logs_written_after_it_starts(): void
    {
        // Arrange

        $collector = resolve(LogsCollector::class);
        $collector->start();

        // Act

        event(new MessageLogged('warning', 'Something happened.', ['order' => 5, 'user' => new \stdClass]));

        $result = $collector->collect();

        // Assert

        $this->assertSame(
            [['level' => 'warning', 'message' => 'Something happened.', 'context' => ['order' => 5, 'user' => 'stdClass']]],
            $result->entries,
        );
        $this->assertSame(0, $result->dropped);
    }

    public function test_it_caps_logs_and_counts_the_excess(): void
    {
        // Arrange

        $collector = resolve(LogsCollector::class);
        $collector->start();

        foreach (range(1, LogsCollector::MAX_LOGS + 2) as $i) {
            event(new MessageLogged('info', 'log '.$i, []));
        }

        // Act

        $result = $collector->collect();

        // Assert

        $this->assertCount(LogsCollector::MAX_LOGS, $result->entries);
        $this->assertSame(2, $result->dropped);
    }

    public function test_it_cuts_a_long_message(): void
    {
        // Arrange

        $collector = resolve(LogsCollector::class);
        $collector->start();

        // Act

        event(new MessageLogged('info', str_repeat('m', 1500), []));

        $result = $collector->collect();

        // Assert

        $this->assertSame(str_repeat('m', 1000), $result->entries[0]['message']);
    }

    public function test_it_describes_an_exception_in_the_context_by_its_class_and_message(): void
    {
        // Arrange

        $collector = resolve(LogsCollector::class);
        $collector->start();

        // Act

        event(new MessageLogged('error', 'failed', ['exception' => new \RuntimeException('boom')]));

        $result = $collector->collect();

        // Assert

        $this->assertSame(['exception' => 'RuntimeException: boom'], $result->entries[0]['context']);
    }

    public function test_it_collects_logs_written_through_the_log_facade(): void
    {
        // Arrange

        $collector = resolve(LogsCollector::class);
        $collector->start();

        // Act

        Log::channel('null')->warning('From the facade.', ['id' => 3]);

        $result = $collector->collect();

        // Assert

        $this->assertSame('warning', $result->entries[0]['level']);
        $this->assertSame(['id' => 3], $result->entries[0]['context']);
    }
}
