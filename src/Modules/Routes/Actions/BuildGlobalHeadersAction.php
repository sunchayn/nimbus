<?php

namespace Sunchayn\Nimbus\Modules\Routes\Actions;

use Illuminate\Support\Arr;
use Sunchayn\Nimbus\Modules\Config\ActiveApplicationResolver;
use Sunchayn\Nimbus\Modules\Config\GlobalHeaderGeneratorTypeEnum;

class BuildGlobalHeadersAction
{
    public function __construct(
        private readonly ActiveApplicationResolver $activeApplicationResolver,
    ) {}

    /**
     * @return array<array-key, scalar|null>
     */
    public function execute(): array
    {
        /** @var array<array-key, mixed> $headers */
        $headers = $this->activeApplicationResolver->getHeaders();

        return array_values(
            Arr::map(
                $headers,
                fn (mixed $value, string $header): array => [
                    'header' => $header,
                    'type' => $value instanceof GlobalHeaderGeneratorTypeEnum ? 'generator' : 'raw',
                    'value' => match (true) {
                        $value instanceof GlobalHeaderGeneratorTypeEnum => $value->value,
                        is_scalar($value) => $value,
                        default => null,
                    },
                ],
            ),
        );
    }
}
