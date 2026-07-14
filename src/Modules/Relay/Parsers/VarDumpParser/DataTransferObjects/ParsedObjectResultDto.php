<?php

declare(strict_types=1);

namespace Sunchayn\Nimbus\Modules\Relay\Parsers\VarDumpParser\DataTransferObjects;

use Illuminate\Contracts\Support\Arrayable;

/**
 * @implements Arrayable<string, string|int|null|array>
 */
readonly class ParsedObjectResultDto implements Arrayable
{
    /**
     * @param  ObjectPropertyDto[]  $properties
     */
    public function __construct(
        public ?string $className,
        public array $properties,
    ) {}

    public function toArray(): array
    {
        return [
            'class' => $this->className,
            'properties' => collect($this->properties)->toArray(),
            'propertiesCount' => count($this->properties),
        ];
    }
}
