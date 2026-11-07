<?php

namespace Workbench\App\Events;

/**
 * An application event that the insight demo dispatches.
 */
class InventorySynced
{
    public function __construct(public readonly int $items) {}
}
