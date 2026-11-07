<?php

declare(strict_types=1);

namespace Sunchayn\Nimbus\Modules\Magic\Services\Insights\Collectors;

/**
 * Keeps a capped list of entries and counts the ones that did not fit.
 *
 * @template TEntry of array<string, mixed>
 */
abstract class ListCollector implements InsightCollectorContract
{
    public const MAX_ENTRIES = 200;

    /** @var list<TEntry> */
    protected array $entries = [];

    protected int $dropped = 0;

    /**
     * @param  TEntry  $entry
     */
    protected function add(array $entry): void
    {
        if (count($this->entries) >= self::MAX_ENTRIES) {
            $this->dropped++;

            return;
        }

        $this->entries[] = $entry;
    }
}
