<?php

declare(strict_types=1);

namespace Sunchayn\Nimbus\Modules\Relay\Services\VarDumpParser\DataTransferObjects;

use Illuminate\Contracts\Support\Arrayable;

/**
 * @implements Arrayable<string, int|array>
 */
readonly class ParsedArrayResultDto implements Arrayable
{
    /**
     * @param  array<string, mixed>  $items
     */
    public function __construct(
        public array $items,
        public bool $numericallyIndexed,
    ) {}

    public function toArray(): array
    {
        return [
            'items' => collect($this->items)->toArray(),
            'length' => count($this->items),
            'numericallyIndexed' => $this->numericallyIndexed,
        ];
    }
}
