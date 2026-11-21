<?php

declare(strict_types=1);

namespace Sunchayn\Nimbus\Modules\Mcp\Enums;

/**
 * What an agent is allowed to do through the MCP server. Each level includes the ones before it.
 */
enum McpLevelEnum: string
{
    case Read = 'read';
    case Send = 'send';
    case Write = 'write';

    public function includes(self $required): bool
    {
        return $this->rank() >= $required->rank();
    }

    private function rank(): int
    {
        return match ($this) {
            self::Read => 1,
            self::Send => 2,
            self::Write => 3,
        };
    }
}
