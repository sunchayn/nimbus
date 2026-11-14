<?php

declare(strict_types=1);

namespace Sunchayn\Nimbus\Modules\Relay\Services\VarDumpParser\DataTransferObjects;

use Illuminate\Contracts\Support\Arrayable;

/**
 * @phpstan-import-type ParsedValueShape from ParsedValueDto
 *
 * @phpstan-type ObjectPropertyShape array{visibility: string, value: ParsedValueShape}
 *
 * @implements Arrayable<string, string|ParsedValueShape>
 */
readonly class ObjectPropertyDto implements Arrayable
{
    public function __construct(
        public string $visibility,
        public ParsedValueDto $value,
    ) {}

    /**
     * @return ObjectPropertyShape
     */
    public function toArray(): array
    {
        return [
            'visibility' => $this->visibility,
            'value' => $this->value->toArray(),
        ];
    }
}
