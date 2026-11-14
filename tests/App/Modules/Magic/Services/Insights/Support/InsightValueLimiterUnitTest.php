<?php

namespace Sunchayn\Nimbus\Tests\App\Modules\Magic\Services\Insights\Support;

use Carbon\CarbonImmutable;
use DateTime;
use Generator;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use RuntimeException;
use stdClass;
use Sunchayn\Nimbus\Modules\Magic\Services\Insights\Support\InsightValueLimiter;

#[CoversClass(InsightValueLimiter::class)]
class InsightValueLimiterUnitTest extends TestCase
{
    #[DataProvider('valuesDataProvider')]
    public function test_it_turns_a_value_into_a_short_scalar(mixed $value, mixed $expected): void
    {
        // Arrange

        $limiter = new InsightValueLimiter;

        // Act & Assert

        $this->assertSame($expected, $limiter->limit($value));
    }

    public static function valuesDataProvider(): Generator
    {
        yield 'null' => [
            'value' => null,
            'expected' => null,
        ];

        yield 'true' => [
            'value' => true,
            'expected' => true,
        ];

        yield 'integer' => [
            'value' => 12,
            'expected' => 12,
        ];

        yield 'float' => [
            'value' => 1.5,
            'expected' => 1.5,
        ];

        yield 'short string' => [
            'value' => 'hello',
            'expected' => 'hello',
        ];

        yield 'string at the limit' => [
            'value' => str_repeat('a', InsightValueLimiter::LIMIT),
            'expected' => str_repeat('a', InsightValueLimiter::LIMIT),
        ];

        yield 'long string' => [
            'value' => str_repeat('a', InsightValueLimiter::LIMIT + 50),
            'expected' => str_repeat('a', InsightValueLimiter::LIMIT),
        ];

        yield 'long multibyte string' => [
            'value' => str_repeat('é', InsightValueLimiter::LIMIT + 5),
            'expected' => str_repeat('é', InsightValueLimiter::LIMIT),
        ];

        yield 'date time' => [
            'value' => new DateTime('2030-05-06 07:08:09 UTC'),
            'expected' => '2030-05-06T07:08:09+00:00',
        ];

        yield 'carbon' => [
            'value' => new CarbonImmutable('2030-05-06 07:08:09', 'UTC'),
            'expected' => '2030-05-06T07:08:09+00:00',
        ];

        yield 'array' => [
            'value' => ['a' => 1, 'b' => [2, 3]],
            'expected' => '{"a":1,"b":[2,3]}',
        ];

        yield 'long array' => [
            'value' => [str_repeat('a', 500)],
            'expected' => mb_substr('["'.str_repeat('a', 500).'"]', 0, InsightValueLimiter::LIMIT),
        ];

        yield 'object' => [
            'value' => new stdClass,
            'expected' => 'stdClass',
        ];
    }

    public function test_it_names_the_type_of_a_resource(): void
    {
        // Arrange

        $limiter = new InsightValueLimiter;
        $resource = fopen('php://memory', 'r');

        // Act

        $limited = $limiter->limit($resource);

        fclose($resource);

        // Assert

        $this->assertSame('resource (stream)', $limited);
    }

    public function test_it_replaces_invalid_utf8_inside_an_array(): void
    {
        // Arrange

        $limiter = new InsightValueLimiter;

        // Act

        $limited = $limiter->limit(["bad\xB1"]);

        // Assert

        $this->assertIsString($limited);
        $this->assertStringStartsWith('["bad', $limited);
    }

    public function test_it_limits_every_value_of_a_context(): void
    {
        // Arrange

        $limiter = new InsightValueLimiter;

        // Act

        $context = $limiter->limitContext([
            'order' => 5,
            'user' => new stdClass,
            'note' => str_repeat('a', InsightValueLimiter::LIMIT + 1),
        ]);

        // Assert

        $this->assertSame(
            ['order' => 5, 'user' => 'stdClass', 'note' => str_repeat('a', InsightValueLimiter::LIMIT)],
            $context,
        );
    }

    public function test_it_describes_a_throwable_in_a_context_by_class_and_message(): void
    {
        // Arrange

        $limiter = new InsightValueLimiter;

        // Act

        $context = $limiter->limitContext([
            'exception' => new RuntimeException(str_repeat('m', InsightValueLimiter::LIMIT + 10)),
        ]);

        // Assert

        $this->assertSame(
            RuntimeException::class.': '.str_repeat('m', InsightValueLimiter::LIMIT),
            $context['exception'],
        );
    }

    public function test_it_keeps_only_the_first_twenty_entries_of_a_context(): void
    {
        // Arrange

        $limiter = new InsightValueLimiter;

        // Act

        $context = $limiter->limitContext(array_fill(0, 30, 'value'));

        // Assert

        $this->assertCount(20, $context);
        $this->assertSame(range(0, 19), array_keys($context));
    }

    public function test_it_returns_an_empty_context_for_an_empty_context(): void
    {
        // Arrange

        $limiter = new InsightValueLimiter;

        // Act & Assert

        $this->assertSame([], $limiter->limitContext([]));
    }
}
