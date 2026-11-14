<?php

namespace Sunchayn\Nimbus\Tests\App\Modules\Magic\Services\Insights\Results;

use Generator;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Sunchayn\Nimbus\Modules\Magic\Services\Insights\Results\QueriesResult;

#[CoversClass(QueriesResult::class)]
class QueriesResultUnitTest extends TestCase
{
    #[DataProvider('halvingDataProvider')]
    public function test_it_keeps_the_first_half_and_counts_the_rest_as_dropped(
        int $count,
        int $alreadyDropped,
        int $expectedKept,
        int $expectedDropped,
    ): void {
        // Arrange

        $result = new QueriesResult($this->queries($count), $alreadyDropped);

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

        $result = new QueriesResult($this->queries(4));

        // Act

        $halved = $result->halved();

        // Assert

        $this->assertSame(['select 0', 'select 1'], array_column($halved->entries, 'sql'));
    }

    public function test_it_does_not_change_itself_when_halved(): void
    {
        // Arrange

        $result = new QueriesResult($this->queries(4), 1);

        // Act

        $result->halved();

        // Assert

        $this->assertCount(4, $result->entries);
        $this->assertSame(1, $result->dropped);
    }

    /**
     * @return list<array{sql: string, bindings: list<mixed>, time_ms: float, connection: string}>
     */
    private function queries(int $count): array
    {
        return array_map(
            fn (int $number): array => ['sql' => 'select '.$number, 'bindings' => [], 'time_ms' => 1.0, 'connection' => 'c'],
            $count === 0 ? [] : range(0, $count - 1),
        );
    }
}
