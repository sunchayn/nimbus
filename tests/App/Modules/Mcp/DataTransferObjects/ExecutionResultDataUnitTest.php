<?php

declare(strict_types=1);

namespace Sunchayn\Nimbus\Tests\App\Modules\Mcp\DataTransferObjects;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Sunchayn\Nimbus\Modules\Magic\DataTransferObjects\InsightData;
use Sunchayn\Nimbus\Modules\Mcp\DataTransferObjects\ExecutionResultData;

#[CoversClass(ExecutionResultData::class)]
class ExecutionResultDataUnitTest extends TestCase
{
    public function test_it_round_trips_through_an_array(): void
    {
        // Arrange

        $executionResultData = new ExecutionResultData(
            status: 201,
            headers: ['x-a' => 'b'],
            body: '{"id":1}',
            durationMs: 12.5,
        );

        // Act

        $restored = ExecutionResultData::fromArray($executionResultData->toArray());

        // Assert

        $this->assertSame(
            $executionResultData->toArray(),
            $restored->toArray(),
        );
    }

    public function test_it_uses_defaults_for_missing_values(): void
    {
        // Act

        $executionResultData = ExecutionResultData::fromArray([]);

        // Assert

        $this->assertSame(
            [
                'status' => 0,
                'headers' => [],
                'body' => '',
                'durationMs' => 0.0,
                'insight' => null,
            ],
            $executionResultData->toArray(),
        );
    }

    public function test_it_casts_header_values_to_strings(): void
    {
        // Act

        $executionResultData = ExecutionResultData::fromArray([
            'headers' => ['content-length' => 12],
        ]);

        // Assert

        $this->assertSame(
            ['content-length' => '12'],
            $executionResultData->headers,
        );
    }

    public function test_it_reads_the_insight(): void
    {
        // Arrange

        $insight = InsightData::fromArray(['queries' => [], 'durationMs' => 3.0])->toArray();

        // Act

        $executionResultData = ExecutionResultData::fromArray(['insight' => $insight]);

        // Assert

        $this->assertInstanceOf(
            InsightData::class,
            $executionResultData->insight,
        );

        $this->assertSame(
            $insight,
            $executionResultData->toArray()['insight'],
        );
    }
}
