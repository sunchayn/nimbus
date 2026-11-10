<?php

declare(strict_types=1);

namespace Sunchayn\Nimbus\Modules\Schemas\ValueObjects;

/**
 * Extra information about a property that plain JSON Schema keywords cannot express.
 *
 * The title is the label of the field. Hints become the description of the property. The lookup tells the client where
 * the values of an exists rule live. Skipping autofill keeps a prohibited field out of generated payloads.
 * The date format is the PHP format string a date value must follow.
 */
final readonly class PropertyAnnotations
{
    /**
     * @param  string[]  $hints
     * @param  array{table: string, column: string}|null  $lookup
     */
    public function __construct(
        public array $hints = [],
        public ?array $lookup = null,
        public bool $skipAutofill = false,
        public ?string $dateFormat = null,
        public ?string $title = null,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function toJsonSchema(): array
    {
        $annotations = [];

        if ($this->title !== null) {
            $annotations['title'] = $this->title;
        }

        if ($this->hints !== []) {
            $annotations['description'] = implode('. ', $this->hints);
        }

        if ($this->lookup !== null) {
            $annotations['lookup'] = $this->lookup;
        }

        if ($this->skipAutofill) {
            $annotations['skipAutofill'] = true;
        }

        if ($this->dateFormat !== null) {
            $annotations['dateFormat'] = $this->dateFormat;
        }

        return $annotations;
    }
}
