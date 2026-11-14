<?php

declare(strict_types=1);

namespace Sunchayn\Nimbus\Modules\Magic\Services\Insights\Results;

/**
 * A capped list of entries of one kind, with the count of entries that did not fit.
 *
 * @template-covariant TEntry of array<string, mixed>
 */
abstract class InsightListResult extends InsightCollectorResult
{
    /**
     * @param  list<TEntry>  $entries
     */
    final public function __construct(
        public readonly array $entries = [],
        public readonly int $dropped = 0,
    ) {}

    public function count(): int
    {
        return count($this->entries);
    }

    /**
     * Keeps the first half of the entries and counts the rest as dropped.
     */
    public function halved(): static
    {
        $keep = intdiv($this->count(), 2);

        return new static(
            entries: array_slice($this->entries, 0, $keep),
            dropped: $this->dropped + $this->count() - $keep,
        );
    }
}
