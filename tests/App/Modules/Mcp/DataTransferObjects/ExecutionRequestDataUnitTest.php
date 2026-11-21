<?php

declare(strict_types=1);

namespace Sunchayn\Nimbus\Tests\App\Modules\Mcp\DataTransferObjects;

use Generator;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Sunchayn\Nimbus\Modules\Mcp\DataTransferObjects\ExecutionRequestData;

#[CoversClass(ExecutionRequestData::class)]
class ExecutionRequestDataUnitTest extends TestCase
{
    public function test_it_round_trips_through_an_array(): void
    {
        // Arrange

        $executionRequestData = new ExecutionRequestData(
            method: 'POST',
            path: '/api/users',
            query: ['page' => '2'],
            headers: ['accept' => 'application/json'],
            body: ['name' => 'Jane'],
            transactionMode: true,
            environment: 'staging',
        );

        // Act

        $restored = ExecutionRequestData::fromArray($executionRequestData->toArray());

        // Assert

        $this->assertEquals(
            $executionRequestData,
            $restored,
        );
    }

    public function test_it_uses_defaults_for_missing_values(): void
    {
        // Act

        $executionRequestData = ExecutionRequestData::fromArray([]);

        // Assert

        $this->assertSame(
            [
                'method' => 'GET',
                'path' => '/',
                'query' => [],
                'headers' => [],
                'body' => [],
                'transactionMode' => false,
                'environment' => 'current',
            ],
            $executionRequestData->toArray(),
        );
    }

    /**
     * @param  array<array-key, mixed>|string  $expectedBody
     */
    #[DataProvider('bodies')]
    public function test_it_reads_the_body(mixed $body, array|string $expectedBody): void
    {
        // Act

        $executionRequestData = ExecutionRequestData::fromArray(['body' => $body]);

        // Assert

        $this->assertSame(
            $expectedBody,
            $executionRequestData->body,
        );
    }

    public static function bodies(): Generator
    {
        yield 'raw string' => [
            'body' => 'raw=1',
            'expectedBody' => 'raw=1',
        ];

        yield 'structured body' => [
            'body' => ['name' => 'Jane'],
            'expectedBody' => ['name' => 'Jane'],
        ];

        yield 'null body' => [
            'body' => null,
            'expectedBody' => [],
        ];
    }
}
