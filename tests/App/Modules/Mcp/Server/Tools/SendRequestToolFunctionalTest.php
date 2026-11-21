<?php

declare(strict_types=1);

namespace Sunchayn\Nimbus\Tests\App\Modules\Mcp\Server\Tools;

use PHPUnit\Framework\Attributes\CoversClass;
use Sunchayn\Nimbus\Modules\Mcp\Server\Tools\SendRequestTool;
use Sunchayn\Nimbus\Tests\TestCase;

#[CoversClass(SendRequestTool::class)]
class SendRequestToolFunctionalTest extends TestCase
{
    public function test_it_defines_a_tool_that_is_not_read_only(): void
    {
        // Arrange

        $tool = resolve(SendRequestTool::class);

        // Act

        $definition = $tool->definition();

        // Assert

        $this->assertSame(
            'nimbus_send_request',
            $definition->name,
        );

        $this->assertArrayNotHasKey(
            'required',
            $definition->inputSchema,
        );

        $this->assertNull($definition->annotations);
    }
}
