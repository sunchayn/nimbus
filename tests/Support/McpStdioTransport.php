<?php

declare(strict_types=1);

namespace Sunchayn\Nimbus\Tests\Support;

use Mcp\Server\Transport\StdioTransport;

/**
 * Keeps its streams open after the run, so a test can read what the server wrote.
 */
class McpStdioTransport extends StdioTransport
{
    public function close(): void
    {
        // The streams belong to the test.
    }
}
