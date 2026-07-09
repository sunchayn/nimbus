<?php

namespace Sunchayn\Nimbus\Modules\Collections\Contracts;

use Sunchayn\Nimbus\Modules\Collections\DataTransferObjects\CollectionData;

/**
 * Persists shareable environment collections.
 *
 * Implementations decide where collections live (git-tracked JSON files by
 * default; a database or remote service are equally valid drivers). The
 * frontend syncs its full collection list on every change, so the store only
 * needs to read everything and reconcile the whole set.
 */
interface CollectionStoreContract
{
    /**
     * All persisted collections, ordered.
     *
     * @return array<int, CollectionData>
     */
    public function all(): array;

    /**
     * Replace the persisted set with the given collections.
     *
     * Implementations must remove any previously stored collection that is not
     * present in `$collections` so the store mirrors the incoming list exactly.
     *
     * @param  array<int, CollectionData>  $collections
     */
    public function sync(array $collections): void;
}
