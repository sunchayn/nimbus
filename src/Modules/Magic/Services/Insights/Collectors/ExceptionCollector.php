<?php

namespace Sunchayn\Nimbus\Modules\Magic\Services\Insights\Collectors;

use Illuminate\Contracts\Debug\ExceptionHandler;
use Illuminate\Contracts\Events\Dispatcher;
use Illuminate\Log\Events\MessageLogged;
use Sunchayn\Nimbus\Modules\Magic\Services\Insights\Results\ExceptionResult;
use Throwable;

/**
 * @phpstan-import-type ExceptionInfo from ExceptionResult
 */
class ExceptionCollector implements InsightCollectorContract
{
    /** @var ExceptionInfo|null */
    private ?array $exception = null;

    public function __construct(private readonly Dispatcher $dispatcher) {}

    public function start(): void
    {
        $this->dispatcher->listen(MessageLogged::class, function (MessageLogged $log): void {
            $exception = $log->context['exception'] ?? null;

            if ($exception instanceof Throwable) {
                $this->record($exception);
            }
        });

        $this->listenForRenderedExceptions();
    }

    private function record(Throwable $throwable): void
    {
        // The first exception is the cause, later ones are usually a result of it.
        if ($this->exception !== null) {
            return;
        }

        $trace = [['file' => $throwable->getFile(), 'line' => $throwable->getLine(), 'call' => '']];

        foreach ($throwable->getTrace() as $frame) {
            if (isset($frame['file'], $frame['line']) && count($trace) < 10) {
                $trace[] = ['file' => $frame['file'], 'line' => $frame['line'], 'call' => ($frame['class'] ?? '').($frame['type'] ?? '').$frame['function']];
            }
        }

        $this->exception = [
            'class' => $throwable::class,
            'message' => mb_substr($throwable->getMessage(), 0, 1000),
            'file' => $throwable->getFile(),
            'line' => $throwable->getLine(),
            'trace' => array_map(fn (array $frame): array => $frame + ['code' => $this->codeAround($frame['file'], $frame['line'])], $trace),
        ];
    }

    public function collect(): ExceptionResult
    {
        return new ExceptionResult($this->exception);
    }

    /**
     * Handlers can skip reporting for expected exceptions such as validation errors, so rendering is observed too.
     */
    private function listenForRenderedExceptions(): void
    {
        $exceptionHandler = app(ExceptionHandler::class);

        // A custom handler without `renderable` still works, it only misses exceptions that are not reported.
        try {
            $exceptionHandler->renderable(function (Throwable $throwable): null {
                $this->record($throwable);

                return null;
            });
        } catch (Throwable) {
        }
    }

    /**
     * @return array<int, string>
     */
    private function codeAround(string $file, int $line): array
    {
        if (! is_readable($file)) {
            return [];
        }

        $lines = @file($file, FILE_IGNORE_NEW_LINES) ?: [];
        $code = [];

        for ($number = max(1, $line - 4); $number <= min(count($lines), $line + 4); $number++) {
            $code[$number] = mb_substr($lines[$number - 1], 0, 300);
        }

        return $code;
    }
}
