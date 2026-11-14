<?php

declare(strict_types=1);

namespace Sunchayn\Nimbus\Modules\Relay\Services\VarDumpParser\DataTransferObjects;

use Illuminate\Contracts\Support\Arrayable;

/**
 * @implements Arrayable<string, array<string, mixed>|int|bool>
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

    /**
     * @return array{items: array<string, mixed>, length: int, numericallyIndexed: bool}
     */
    public function toArray(): array
    {
        return [
            'items' => collect($this->items)->toArray(),
            'length' => count($this->items),
            'numericallyIndexed' => $this->numericallyIndexed,
        ];
    }
}
