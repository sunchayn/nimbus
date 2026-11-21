<?php

declare(strict_types=1);

namespace Sunchayn\Nimbus\Tests\App\Modules\Mcp\Server\Tools;

use PHPUnit\Framework\Attributes\CoversClass;
use Sunchayn\Nimbus\Modules\Mcp\Server\Tools\SaveRequestTool;
use Sunchayn\Nimbus\Tests\TestCase;

#[CoversClass(SaveRequestTool::class)]
class SaveRequestToolFunctionalTest extends TestCase
{
    public function test_it_defines_a_tool_that_is_not_read_only(): void
    {
        // Arrange

        $tool = resolve(SaveRequestTool::class);

        // Act

        $definition = $tool->definition();

        // Assert

        $this->assertSame(
            'nimbus_save_request',
            $definition->name,
        );

        $this->assertSame(
            [
                'name',
                'method',
                'endpoint',
            ],
            $definition->inputSchema['required'],
        );

        $this->assertNull($definition->annotations);
    }
}
