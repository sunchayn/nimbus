<?php

declare(strict_types=1);

namespace Sunchayn\Nimbus\Modules\Config;

/**
 * Defines available random value generation strategies for global headers..
 */
enum GlobalHeaderGeneratorTypeEnum: string
{
    case Uuid = 'UUID';

    case Email = 'Email';

    case String = 'String';
}
