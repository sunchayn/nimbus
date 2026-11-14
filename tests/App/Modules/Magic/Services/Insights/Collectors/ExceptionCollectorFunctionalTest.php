<?php

namespace Sunchayn\Nimbus\Tests\App\Modules\Magic\Services\Insights\Collectors;

use Illuminate\Contracts\Debug\ExceptionHandler;
use Illuminate\Http\Request;
use Illuminate\Log\Events\MessageLogged;
use PHPUnit\Framework\Attributes\CoversClass;
use Sunchayn\Nimbus\Modules\Magic\Services\Insights\Collectors\ExceptionCollector;
use Sunchayn\Nimbus\Tests\TestCase;

#[CoversClass(ExceptionCollector::class)]
class ExceptionCollectorFunctionalTest extends TestCase
{
    public function test_it_keeps_the_first_logged_exception_only(): void
    {
        // Arrange

        $collector = resolve(ExceptionCollector::class);
        $collector->start();

        // Act

        event(new MessageLogged('error', 'first', ['exception' => new \RuntimeException('first')]));
        event(new MessageLogged('error', 'second', ['exception' => new \LogicException('second')]));

        $result = $collector->collect();

        // Assert

        $this->assertSame(\RuntimeException::class, $result->exception['class']);
        $this->assertSame('first', $result->exception['message']);
        $this->assertNotEmpty($result->exception['trace'][0]['code']);
    }

    public function test_it_ignores_logs_without_an_exception(): void
    {
        // Arrange

        $collector = resolve(ExceptionCollector::class);
        $collector->start();

        // Act

        event(new MessageLogged('error', 'plain', ['exception' => 'not a throwable']));

        // Assert

        $this->assertNull($collector->collect()->exception);
    }

    public function test_it_collects_an_exception_that_is_rendered_without_being_reported(): void
    {
        // Arrange

        $collector = resolve(ExceptionCollector::class);
        $collector->start();

        // Act

        resolve(ExceptionHandler::class)->render(Request::create('/'), new \LogicException('rendered'));

        $result = $collector->collect();

        // Assert

        $this->assertSame('rendered', $result->exception['message'] ?? null);
    }

    public function test_it_collects_nothing_when_no_exception_happens(): void
    {
        // Arrange

        $collector = resolve(ExceptionCollector::class);
        $collector->start();

        // Act

        $result = $collector->collect();

        // Assert

        $this->assertNull($result->exception);
    }

    public function test_it_cuts_a_long_message(): void
    {
        // Arrange

        $collector = resolve(ExceptionCollector::class);
        $collector->start();

        // Act

        event(new MessageLogged('error', 'long', ['exception' => new \RuntimeException(str_repeat('m', 1500))]));

        $result = $collector->collect();

        // Assert

        $this->assertSame(str_repeat('m', 1000), $result->exception['message']);
    }

    public function test_it_describes_the_origin_of_the_exception_as_the_first_frame(): void
    {
        // Arrange

        $collector = resolve(ExceptionCollector::class);
        $collector->start();

        $exception = new \RuntimeException('origin');

        // Act

        event(new MessageLogged('error', 'origin', ['exception' => $exception]));

        $result = $collector->collect();

        // Assert

        $this->assertSame($exception->getFile(), $result->exception['file']);
        $this->assertSame($exception->getLine(), $result->exception['line']);
        $this->assertSame(['file' => $exception->getFile(), 'line' => $exception->getLine(), 'call' => ''], array_diff_key($result->exception['trace'][0], ['code' => true]));
    }

    public function test_it_adds_the_lines_around_each_frame_in_the_trace(): void
    {
        // Arrange

        $collector = resolve(ExceptionCollector::class);
        $collector->start();

        $exception = new \RuntimeException('origin');

        // Act

        event(new MessageLogged('error', 'origin', ['exception' => $exception]));

        $result = $collector->collect();

        $code = $result->exception['trace'][0]['code'];

        // Assert

        $this->assertArrayHasKey($exception->getLine(), $code);
        $this->assertLessThanOrEqual(9, count($code));
        $this->assertStringContainsString("new \\RuntimeException('origin')", $code[$exception->getLine()]);
    }

    public function test_it_keeps_at_most_ten_frames(): void
    {
        // Arrange

        $collector = resolve(ExceptionCollector::class);
        $collector->start();

        $exception = $this->deepException(30);

        // Act

        event(new MessageLogged('error', 'deep', ['exception' => $exception]));

        $result = $collector->collect();

        // Assert

        $this->assertGreaterThan(10, count($exception->getTrace()));
        $this->assertCount(10, $result->exception['trace']);
    }

    public function test_it_names_the_call_of_a_frame_with_its_class_and_method(): void
    {
        // Arrange

        $collector = resolve(ExceptionCollector::class);
        $collector->start();

        $exception = $this->deepException(1);

        // Act

        event(new MessageLogged('error', 'deep', ['exception' => $exception]));

        $result = $collector->collect();

        // Assert

        $this->assertSame(self::class.'->deepException', $result->exception['trace'][1]['call']);
    }

    private function deepException(int $depth): \Throwable
    {
        return $depth <= 1
            ? new \RuntimeException('deep')
            : $this->deepException($depth - 1);
    }
}
