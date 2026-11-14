<?php

declare(strict_types=1);

namespace Sunchayn\Nimbus\Modules\Magic\Services\Insights\Results;

/**
 * @phpstan-type Frame array{file: string, line: int, call: string, code: array<int, string>}
 * @phpstan-type ExceptionInfo array{class: string, message: string, file: string, line: int, trace: list<Frame>}
 */
class ExceptionResult extends InsightCollectorResult
{
    /**
     * @param  ExceptionInfo|null  $exception
     */
    public function __construct(public readonly ?array $exception = null) {}
}
