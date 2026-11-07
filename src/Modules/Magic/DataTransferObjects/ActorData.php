<?php

declare(strict_types=1);

namespace Sunchayn\Nimbus\Modules\Magic\DataTransferObjects;

class ActorData
{
    public function __construct(
        public readonly string $id,
        public readonly string $label,
        public readonly string $model,
    ) {}

    /**
     * @return array{id: string, label: string, model: string}
     */
    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'label' => $this->label,
            'model' => $this->model,
        ];
    }
}
