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
                function (mixed $value, string $header): array {
                    $generator = GlobalHeaderGeneratorTypeEnum::tryFromAlias($value);

                    if ($generator instanceof \Sunchayn\Nimbus\Modules\Config\GlobalHeaderGeneratorTypeEnum) {
                        return [
                            'header' => $header,
                            'type' => 'generator',
                            'value' => $generator->value,
                        ];
                    }

                    return [
                        'header' => $header,
                        'type' => 'raw',
                        'value' => is_scalar($value) ? $value : null,
                    ];
                },
            ),
        );
    }
}
