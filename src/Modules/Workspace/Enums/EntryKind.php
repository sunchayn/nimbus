<?php

declare(strict_types=1);

namespace Sunchayn\Nimbus\Modules\Workspace\Enums;

enum EntryKind: string
{
    case Folder = 'folder';

    case Request = 'request';
}
