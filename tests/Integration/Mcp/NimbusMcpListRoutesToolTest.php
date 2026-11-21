<?php

declare(strict_types=1);

namespace Sunchayn\Nimbus\Tests\Integration\Mcp;

use PHPUnit\Framework\Attributes\CoversClass;
use Sunchayn\Nimbus\Modules\Mcp\Actions\ListRoutesAction;
use Sunchayn\Nimbus\Modules\Mcp\Server\Tools\ListRoutesTool;
use Sunchayn\Nimbus\Tests\App\Modules\Mcp\Concerns\FakesExtractedRoutes;
use Sunchayn\Nimbus\Tests\Concerns\InteractsWithMcpServer;
use Sunchayn\Nimbus\Tests\TestCase;

#[CoversClass(ListRoutesTool::class)]
class NimbusMcpListRoutesToolTest extends TestCase
{
    use FakesExtractedRoutes;
    use InteractsWithMcpServer;

    public function test_it_passes_the_filters_to_the_list_routes_action(): void
    {
        // Arrange

        $listRoutesActionMock = $this->mock(ListRoutesAction::class);

        // Anticipate

        $listRoutesActionMock
            ->shouldReceive('execute')
            ->andReturn(
                [
                    'routes' => [],
                    'total' => 0,
                    'truncated' => false,
                ],
            );

        // Act

        $this->callTool(
            name: 'nimbus_list_routes',
            arguments: [
                'search' => 'users.store',
                'method' => 'POST',
                'limit' => 5,
            ],
        );

        // Assert

        $listRoutesActionMock
            ->shouldHaveReceived('execute')
            ->once()
            ->withArgs(function (?string $search, ?string $method, ?string $prefix, int $limit, ?string $application): bool {
                $this->assertSame(
                    'users.store',
                    $search,
                );

                $this->assertSame(
                    'POST',
                    $method,
                );

                $this->assertNull($prefix);

                $this->assertSame(
                    5,
                    $limit,
                );

                $this->assertNull($application);

                return true;
            });
    }

    public function test_it_returns_the_listed_routes_as_structured_content(): void
    {
        // Arrange

        $listRoutesActionMock = $this->mock(ListRoutesAction::class);

        // Anticipate

        $listRoutesActionMock
            ->shouldReceive('execute')
            ->andReturn(
                [
                    'routes' => [
                        ['id' => 'POST api/users'],
                    ],
                    'total' => 1,
                    'truncated' => false,
                ],
            );

        // Act

        $response = $this->callTool(name: 'nimbus_list_routes');

        // Assert

        $response->assertOk();

        $this->assertSame(
            [
                'routes' => [
                    ['id' => 'POST api/users'],
                ],
                'total' => 1,
                'truncated' => false,
            ],
            $response->structuredContent(),
        );
    }

    public function test_it_integrates(): void
    {
        // Arrange

        $this->fakeExtractedRoutes();

        // Act

        $response = $this->callTool(
            name: 'nimbus_list_routes',
            arguments: [
                'method' => 'POST',
            ],
        );

        // Assert

        $response
            ->assertOk()
            ->assertSee('POST api/users')
            ->assertDontSee('GET api/users');
    }
}
