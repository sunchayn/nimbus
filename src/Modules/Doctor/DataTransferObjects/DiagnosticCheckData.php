<?php

declare(strict_types=1);

namespace Sunchayn\Nimbus\Modules\Doctor\DataTransferObjects;

use Sunchayn\Nimbus\Modules\Doctor\Enums\DiagnosticStatusEnum;

readonly class DiagnosticCheckData
{
    public function __construct(
        public string $name,
        public DiagnosticStatusEnum $status,
        public string $message,
    ) {}

    /**
     * @return array{name: string, status: string, message: string}
     */
    public function toArray(): array
    {
        return [
            'name' => $this->name,
            'status' => $this->status->value,
            'message' => $this->message,
        ];
    }
}
