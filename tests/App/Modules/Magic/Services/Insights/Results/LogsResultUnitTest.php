<?php

namespace Sunchayn\Nimbus\Tests\App\Modules\Magic\Services\Insights\Results;

use Generator;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Sunchayn\Nimbus\Modules\Magic\Services\Insights\Results\LogsResult;

#[CoversClass(LogsResult::class)]
class LogsResultUnitTest extends TestCase
{
    #[DataProvider('halvingDataProvider')]
    public function test_it_keeps_the_first_half_and_counts_the_rest_as_dropped(
        int $count,
        int $alreadyDropped,
        int $expectedKept,
        int $expectedDropped,
    ): void {
        // Arrange

        $result = new LogsResult($this->logs($count), $alreadyDropped);

        // Act

        $halved = $result->halved();

        // Assert

        $this->assertCount($expectedKept, $halved->entries);
        $this->assertSame($expectedDropped, $halved->dropped);
    }

    public static function halvingDataProvider(): Generator
    {
        yield 'even count' => [
            'count' => 4,
            'alreadyDropped' => 0,
            'expectedKept' => 2,
            'expectedDropped' => 2,
        ];

        yield 'odd count' => [
            'count' => 5,
            'alreadyDropped' => 0,
            'expectedKept' => 2,
            'expectedDropped' => 3,
        ];

        yield 'single item' => [
            'count' => 1,
            'alreadyDropped' => 0,
            'expectedKept' => 0,
            'expectedDropped' => 1,
        ];

        yield 'empty' => [
            'count' => 0,
            'alreadyDropped' => 0,
            'expectedKept' => 0,
            'expectedDropped' => 0,
        ];

        yield 'adds to what was already dropped' => [
            'count' => 4,
            'alreadyDropped' => 10,
            'expectedKept' => 2,
            'expectedDropped' => 12,
        ];
    }

    public function test_it_keeps_the_oldest_entries_when_halved(): void
    {
        // Arrange

        $result = new LogsResult($this->logs(4));

        // Act

        $halved = $result->halved();

        // Assert

        $this->assertSame(['log 0', 'log 1'], array_column($halved->entries, 'message'));
    }

    public function test_it_does_not_change_itself_when_halved(): void
    {
        // Arrange

        $result = new LogsResult($this->logs(4), 1);

        // Act

        $result->halved();

        // Assert

        $this->assertCount(4, $result->entries);
        $this->assertSame(1, $result->dropped);
    }

    /**
     * @return list<array{level: string, message: string, context: array<string, mixed>}>
     */
    private function logs(int $count): array
    {
        return array_map(
            fn (int $number): array => ['level' => 'info', 'message' => 'log '.$number, 'context' => []],
            $count === 0 ? [] : range(0, $count - 1),
        );
    }
}
