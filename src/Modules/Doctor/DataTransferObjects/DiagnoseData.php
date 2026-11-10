<?php

declare(strict_types=1);

namespace Sunchayn\Nimbus\Modules\Doctor\DataTransferObjects;

class DiagnoseData
{
    public function __construct(
        public readonly string $pageUrl,
        public readonly string $baseUrl,
    ) {}
}
