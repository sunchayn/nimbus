<?php

namespace Sunchayn\Nimbus\Modules\Relay\Services\VarDumpParser\DataTransferObjects;

use Illuminate\Contracts\Support\Arrayable;

/**
 * @implements Arrayable<string, string|array>
 */
class ParseResultDto implements Arrayable
{
    /**
     * @param  ParsedValueDto[]  $dumps
     */
    public function __construct(
        public readonly ?string $source,
        public readonly array $dumps,
    ) {}

    public static function empty(): self
    {
        return new self(
            source: null,
            dumps: [],
        );
    }

    public function toArray(): array
    {
        return [
            'source' => $this->source,
            'dumps' => collect($this->dumps)->toArray(),
        ];
    }
}
