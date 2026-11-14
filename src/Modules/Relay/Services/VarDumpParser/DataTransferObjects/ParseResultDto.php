<?php

namespace Sunchayn\Nimbus\Modules\Relay\Services\VarDumpParser\DataTransferObjects;

use Illuminate\Contracts\Support\Arrayable;

/**
 * @phpstan-import-type ParsedValueShape from ParsedValueDto
 *
 * @implements Arrayable<string, string|null|array<int, ParsedValueShape>>
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

    /**
     * @return array{source: ?string, dumps: array<int, ParsedValueShape>}
     */
    public function toArray(): array
    {
        return [
            'source' => $this->source,
            'dumps' => collect($this->dumps)->toArray(),
        ];
    }
}
