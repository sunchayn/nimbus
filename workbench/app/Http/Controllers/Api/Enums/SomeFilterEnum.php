<?php

namespace Workbench\App\Http\Controllers\Api\Enums;

enum SomeFilterEnum: string
{
    case Canceled = 'canceled';

    case Pending = 'pending';

    case Available = 'available';

    case Closed = 'closed';
}
