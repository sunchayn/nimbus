<?php

declare(strict_types=1);

namespace Sunchayn\Nimbus\Modules\Config\Enums;

/**
 * Environment ids that Nimbus treats in a special way.
 */
enum EnvironmentIdEnum: string
{
    case Current = 'current';

    case Production = 'production';
}
