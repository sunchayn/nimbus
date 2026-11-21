<?php

declare(strict_types=1);

namespace Sunchayn\Nimbus\Tests\App\Modules\Mcp\DataTransferObjects;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Sunchayn\Nimbus\Modules\Magic\DataTransferObjects\InsightData;
use Sunchayn\Nimbus\Modules\Magic\Services\Insights\Results\ExceptionResult;
use Sunchayn\Nimbus\Modules\Magic\Services\Insights\Results\GatesResult;
use Sunchayn\Nimbus\Modules\Magic\Services\Insights\Results\LogsResult;
use Sunchayn\Nimbus\Modules\Magic\Services\Insights\Results\QueriesResult;
use Sunchayn\Nimbus\Modules\Mcp\DataTransferObjects\InsightSummary;

#[CoversClass(InsightSummary::class)]
class InsightSummaryUnitTest extends TestCase
{
    public function test_it_drops_bindings_code_and_low_level_logs(): void
    {
        // Arrange

        $insightData = $this->insightData();

        // Act

        $entries = InsightSummary::fromInsight($insightData)->toArray();

        // Assert

        $this->assertStringNotContainsString(
            'secret',
            (string) json_encode($entries),
        );

        $this->assertCount(
            1,
            $entries['logs'],
        );

        $this->assertSame(
            ['g:2 c()'],
            $entries['exception']['trace'],
        );
    }

    public function test_it_builds_a_headline(): void
    {
        // Arrange

        $summary = InsightSummary::fromInsight($this->insightData());

        // Act

        $headline = $summary->toHeadline();

        // Assert

        $this->assertSame(
            [
                'exception' => 'E: m',
                'deniedChecks' => 1,
                'queryCount' => 1,
            ],
            $headline,
        );
    }

    public function test_it_has_no_exception_when_the_request_did_not_throw(): void
    {
        // Arrange

        $insightData = new InsightData(
            queries: new QueriesResult([]),
            logs: new LogsResult([]),
            gates: new GatesResult([]),
            exception: new ExceptionResult(null),
        );

        // Act

        $entries = InsightSummary::fromInsight($insightData)->toArray();

        // Assert

        $this->assertNull($entries['exception']);
    }

    /*
     * Generators.
     */

    private function insightData(): InsightData
    {
        $query = [
            'sql' => 'select ?',
            'bindings' => ['secret'],
            'time_ms' => 1.5,
            'offset_ms' => 0.0,
            'connection' => 'testing',
        ];

        $debugLog = ['level' => 'debug', 'message' => 'a', 'context' => []];

        $errorLog = ['level' => 'error', 'message' => 'b', 'context' => []];

        $gate = ['ability' => 'view', 'result' => false, 'arguments' => [], 'user' => null];

        $exception = [
            'class' => 'E',
            'message' => 'm',
            'file' => 'f',
            'line' => 1,
            'trace' => [['file' => 'g', 'line' => 2, 'call' => 'c()', 'code' => [1 => 'x']]],
        ];

        return new InsightData(
            queries: new QueriesResult([$query]),
            logs: new LogsResult([$debugLog, $errorLog]),
            gates: new GatesResult([$gate]),
            exception: new ExceptionResult($exception),
        );
    }
}
