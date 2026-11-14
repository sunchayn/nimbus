<?php

declare(strict_types=1);

namespace Sunchayn\Nimbus\Modules\Magic\Services\Insights\Results;

/**
 * @phpstan-type Gate array{ability: string, result: bool|null, arguments: list<string>, user: string|null}
 */
class GatesResult extends InsightCollectorResult
{
    /**
     * @param  list<Gate>  $gates
     */
    public function __construct(public readonly array $gates = []) {}
}
