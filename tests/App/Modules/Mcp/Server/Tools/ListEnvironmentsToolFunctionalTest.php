<?php

declare(strict_types=1);

namespace Sunchayn\Nimbus\Tests\App\Modules\Mcp\Server\Tools;

use PHPUnit\Framework\Attributes\CoversClass;
use Sunchayn\Nimbus\Modules\Mcp\Server\Tools\ListEnvironmentsTool;
use Sunchayn\Nimbus\Tests\TestCase;

#[CoversClass(ListEnvironmentsTool::class)]
class ListEnvironmentsToolFunctionalTest extends TestCase
{
    public function test_it_defines_a_read_only_tool_without_arguments(): void
    {
        // Arrange

        $tool = resolve(ListEnvironmentsTool::class);

        // Act

        $definition = $tool->definition();

        // Assert

        $this->assertSame(
            'nimbus_list_environments',
            $definition->name,
        );

        $this->assertArrayNotHasKey(
            'required',
            $definition->inputSchema,
        );

        $this->assertTrue($definition->annotations->readOnlyHint);
    }
}
