<?php

declare(strict_types=1);

namespace Sunchayn\Nimbus\Modules\Config\Enums;

enum RoutesProcessingStrategyEnum: string
{
    case OpenAPI = 'OpenAPI Specification';

    case AutoDetect = 'Laravel Routes';
}
