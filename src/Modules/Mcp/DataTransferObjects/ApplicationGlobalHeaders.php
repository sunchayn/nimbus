<?php

declare(strict_types=1);

namespace Sunchayn\Nimbus\Modules\Mcp\DataTransferObjects;

use Sunchayn\Nimbus\Modules\Config\ActiveApplicationResolver;
use Sunchayn\Nimbus\Modules\Config\GlobalHeaderGeneratorTypeEnum;

/**
 * The global headers of an application, split by how their value is made.
 * A fixed header is sent as it is. A generated header names a generator, and the agent supplies the value.
 */
final readonly class ApplicationGlobalHeaders
{
    /**
     * @param  array<string, string>  $fixed
     * @param  array<string, string>  $generated  The header names with their generator alias, such as `$uuid`.
     */
    public function __construct(
        public array $fixed,
        public array $generated,
    ) {}

    public static function fromResolver(ActiveApplicationResolver $resolver): self
    {
        $fixed = [];
        $generated = [];

        foreach ($resolver->getHeaders() as $name => $value) {
            if (GlobalHeaderGeneratorTypeEnum::tryFromAlias($value) instanceof GlobalHeaderGeneratorTypeEnum) {
                $generated[$name] = strtolower(trim((string) $value));

                continue;
            }

            if (is_scalar($value)) {
                $fixed[$name] = (string) $value;
            }
        }

        return new self(
            fixed: $fixed,
            generated: $generated,
        );
    }

    /**
     * @return array{fixed: array<string, string>, generated: array<string, string>}
     */
    public function toArray(): array
    {
        return [
            'fixed' => $this->fixed,
            'generated' => $this->generated,
        ];
    }
}
