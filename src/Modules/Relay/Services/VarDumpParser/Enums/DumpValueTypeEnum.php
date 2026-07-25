<?php

declare(strict_types=1);

namespace Sunchayn\Nimbus\Modules\Relay\Services\VarDumpParser\Enums;

enum DumpValueTypeEnum: string
{
    case Object = 'object';

    case Array = 'array';

    case String = 'string';

    case Constant = 'constant';

    case Uninitialized = 'uninitialized';

    case Number = 'number';

    case Closure = 'closure';

    case Unknown = 'unknown';
}
