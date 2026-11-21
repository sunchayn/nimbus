<?php

declare(strict_types=1);

namespace Sunchayn\Nimbus\Tests\App\Modules\Mcp\Actions;

use PHPUnit\Framework\Attributes\CoversClass;
use Sunchayn\Nimbus\Modules\Mcp\Actions\ListEnvironmentsAction;
use Sunchayn\Nimbus\Tests\TestCase;

#[CoversClass(ListEnvironmentsAction::class)]
class ListEnvironmentsActionFunctionalTest extends TestCase
{
    public function test_it_returns_the_host_first(): void
    {
        // Arrange

        config([
            'nimbus.environments' => [
                'staging' => [
                    'name' => 'Staging',
                    'base_url' => 'https://staging.example.com',
                ],
            ],
        ]);

        $action = resolve(ListEnvironmentsAction::class);

        // Act

        $environments = $action->execute()['environments'];

        // Assert

        $this->assertCount(
            2,
            $environments,
        );

        $this->assertSame(
            'Staging',
            $environments[1]['name'],
        );
    }
}
