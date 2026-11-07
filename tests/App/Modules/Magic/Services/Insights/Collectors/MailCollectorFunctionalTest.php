<?php

namespace Sunchayn\Nimbus\Tests\App\Modules\Magic\Services\Insights\Collectors;

use Illuminate\Mail\Mailable;
use Illuminate\Support\Facades\Mail;
use PHPUnit\Framework\Attributes\CoversClass;
use Sunchayn\Nimbus\Modules\Magic\Services\Insights\Collectors\MailCollector;
use Sunchayn\Nimbus\Tests\TestCase;

#[CoversClass(MailCollector::class)]
class MailCollectorFunctionalTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        config(['mail.default' => 'array']);
    }

    public function test_it_records_the_subject_and_recipients_of_a_message(): void
    {
        // Arrange

        $collector = resolve(MailCollector::class);
        $collector->start();

        // Act

        Mail::raw('Hello', fn ($message) => $message->to(['ada@example.com', 'grace@example.com'])->subject('Welcome'));

        $result = $collector->collect();

        // Assert

        $this->assertSame('Welcome', $result->entries[0]['subject']);
        $this->assertSame(['ada@example.com', 'grace@example.com'], $result->entries[0]['to']);
        $this->assertNull($result->entries[0]['mailable']);
    }

    public function test_it_records_the_class_of_a_mailable(): void
    {
        // Arrange

        $collector = resolve(MailCollector::class);
        $collector->start();

        $mailable = new class extends Mailable
        {
            public function build(): static
            {
                return $this->subject('Receipt')->html('<p>Thanks</p>');
            }
        };

        // Act

        Mail::to('ada@example.com')->send($mailable);

        $result = $collector->collect();

        // Assert

        $this->assertSame($mailable::class, $result->entries[0]['mailable']);
        $this->assertSame('Receipt', $result->entries[0]['subject']);
    }

    public function test_it_limits_the_recipients_of_one_message(): void
    {
        // Arrange

        $collector = resolve(MailCollector::class);
        $collector->start();

        $recipients = array_map(fn (int $i): string => "user{$i}@example.com", range(1, 15));

        // Act

        Mail::raw('Hello', fn ($message) => $message->to($recipients)->subject('Newsletter'));

        $result = $collector->collect();

        // Assert

        $this->assertCount(10, $result->entries[0]['to']);
    }

    public function test_it_caps_the_entries_and_counts_the_excess(): void
    {
        // Arrange

        $collector = resolve(MailCollector::class);
        $collector->start();

        foreach (range(1, MailCollector::MAX_ENTRIES + 2) as $i) {
            Mail::raw('Hello', fn ($message) => $message->to('ada@example.com')->subject('Mail '.$i));
        }

        // Act

        $result = $collector->collect();

        // Assert

        $this->assertCount(MailCollector::MAX_ENTRIES, $result->entries);
        $this->assertSame(2, $result->dropped);
    }
}
