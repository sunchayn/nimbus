<?php

declare(strict_types=1);

namespace Sunchayn\Nimbus\Modules\Relay\Services\Authorization\Enums;

/**
 * Authorization types supported by the relay system.
 */
enum AuthorizationTypeEnum: string
{
    case None = 'none';
    case CurrentUser = 'current-user';
    case Bearer = 'bearer';
    case Basic = 'basic';
    case Impersonate = 'impersonate';
}
