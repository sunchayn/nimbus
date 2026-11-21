<?php

declare(strict_types=1);

namespace Sunchayn\Nimbus\Tests\App\Modules\Mcp\Server\Tools;

use PHPUnit\Framework\Attributes\CoversClass;
use Sunchayn\Nimbus\Modules\Mcp\Server\Tools\DescribeRouteTool;
use Sunchayn\Nimbus\Tests\TestCase;

#[CoversClass(DescribeRouteTool::class)]
class DescribeRouteToolFunctionalTest extends TestCase
{
    public function test_it_defines_a_read_only_tool_that_requires_a_route(): void
    {
        // Arrange

        $tool = resolve(DescribeRouteTool::class);

        // Act

        $definition = $tool->definition();

        // Assert

        $this->assertSame(
            'nimbus_describe_route',
            $definition->name,
        );

        $this->assertSame(
            ['route'],
            $definition->inputSchema['required'],
        );

        $this->assertTrue($definition->annotations->readOnlyHint);
    }
}
