<?php

declare(strict_types=1);

namespace Sunchayn\Nimbus\Tests\App\Modules\Mcp\Server\Tools;

use PHPUnit\Framework\Attributes\CoversClass;
use Sunchayn\Nimbus\Modules\Mcp\Server\Tools\RunRequestTool;
use Sunchayn\Nimbus\Tests\TestCase;

#[CoversClass(RunRequestTool::class)]
class RunRequestToolFunctionalTest extends TestCase
{
    public function test_it_defines_a_tool_that_is_not_read_only(): void
    {
        // Arrange

        $tool = resolve(RunRequestTool::class);

        // Act

        $definition = $tool->definition();

        // Assert

        $this->assertSame(
            'nimbus_run_request',
            $definition->name,
        );

        $this->assertSame(
            ['saved_request_id'],
            $definition->inputSchema['required'],
        );

        $this->assertNull($definition->annotations);
    }
}
