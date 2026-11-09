<?php

declare(strict_types=1);

namespace Sunchayn\Nimbus\Modules\Doctor\Enums;

enum DiagnosticStatusEnum: string
{
    case Ok = 'ok';

    case Warning = 'warning';

    case Failed = 'failed';
}
