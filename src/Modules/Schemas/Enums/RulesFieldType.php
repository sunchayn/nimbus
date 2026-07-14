<?php

declare(strict_types=1);

namespace Sunchayn\Nimbus\Modules\Schemas\Enums;

enum RulesFieldType
{
    /** @example email */
    case ROOT;

    /** @example person.email */
    /** @example persons.*.email */
    case DOT_NOTATION;

    /** @example items.* */
    case ARRAY_OF_PRIMITIVES;
}
