<?php

declare(strict_types=1);

namespace Sunchayn\Nimbus\Tests\App\Modules\Mcp\Server\Tools;

use PHPUnit\Framework\Attributes\CoversClass;
use Sunchayn\Nimbus\Modules\Mcp\Server\Tools\ListSavedRequestsTool;
use Sunchayn\Nimbus\Tests\TestCase;

#[CoversClass(ListSavedRequestsTool::class)]
class ListSavedRequestsToolFunctionalTest extends TestCase
{
    public function test_it_defines_a_read_only_tool_without_required_fields(): void
    {
        // Arrange

        $tool = resolve(ListSavedRequestsTool::class);

        // Act

        $definition = $tool->definition();

        // Assert

        $this->assertSame(
            'nimbus_list_saved_requests',
            $definition->name,
        );

        $this->assertArrayNotHasKey(
            'required',
            $definition->inputSchema,
        );

        $this->assertTrue($definition->annotations->readOnlyHint);
    }
}
