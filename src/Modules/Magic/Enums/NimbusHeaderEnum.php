<?php

declare(strict_types=1);

namespace Sunchayn\Nimbus\Modules\Magic\Enums;

/**
 * The Nimbus control headers that the target application reads on a relayed request.
 */
enum NimbusHeaderEnum: string
{
    case TransactionMode = 'X-Nimbus-Transaction-Mode';

    case Actor = 'X-Nimbus-Actor';
}
