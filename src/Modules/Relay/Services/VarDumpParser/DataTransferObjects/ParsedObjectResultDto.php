<?php

declare(strict_types=1);

namespace Sunchayn\Nimbus\Modules\Relay\Services\VarDumpParser\DataTransferObjects;

use Illuminate\Contracts\Support\Arrayable;

/**
 * @phpstan-import-type ObjectPropertyShape from ObjectPropertyDto
 *
 * @implements Arrayable<string, string|int|null|array<int, ObjectPropertyShape>>
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

    /**
     * @return array{class: ?string, properties: array<int, ObjectPropertyShape>, propertiesCount: int}
     */
    public function toArray(): array
    {
        return [
            'class' => $this->className,
            'properties' => collect($this->properties)->toArray(),
            'propertiesCount' => count($this->properties),
        ];
    }
}
