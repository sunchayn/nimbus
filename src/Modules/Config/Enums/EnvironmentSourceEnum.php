<?php

declare(strict_types=1);

namespace Sunchayn\Nimbus\Modules\Config\Enums;

/**
 * Where an environment comes from.
 */
enum EnvironmentSourceEnum: string
{
    case Current = 'current';

    case Config = 'config';

    case Custom = 'custom';
}
