<?php

declare(strict_types=1);

namespace Sunchayn\Nimbus\Tests\App\Modules\Mcp\Actions;

use PHPUnit\Framework\Attributes\CoversClass;
use Sunchayn\Nimbus\Modules\Magic\Injectors\TymonJwtTokenInjector;
use Sunchayn\Nimbus\Modules\Mcp\Actions\ListApplicationsAction;
use Sunchayn\Nimbus\Tests\TestCase;

#[CoversClass(ListApplicationsAction::class)]
class ListApplicationsActionFunctionalTest extends TestCase
{
    public function test_it_describes_each_application(): void
    {
        // Arrange

        config()->set('nimbus.applications.main.headers', [
            'X-App' => '1.0',
            'X-Request-Id' => '$UUID',
        ]);

        config()->set('nimbus.applications.other.auth', [
            'guard' => 'api',
            'special' => ['injector' => 'tymon_jwt'],
        ]);

        $action = resolve(ListApplicationsAction::class);

        // Act

        $applications = $action->execute()['applications'];

        // Assert

        $this->assertSame(
            [
                'key' => 'main',
                'name' => 'Main Application',
                'routesPrefix' => 'api',
                'versioned' => false,
                'extractionStrategy' => 'Laravel Routes',
                'authGuard' => 'web',
                'specialAuthInjector' => null,
                'globalHeaders' => [
                    'fixed' => ['X-App' => '1.0'],
                    'generated' => ['X-Request-Id' => '$uuid'],
                ],
                'default' => true,
            ],
            $applications[0],
        );

        $this->assertSame(
            [
                'key' => 'other',
                'name' => 'Other Application',
                'routesPrefix' => 'other-api',
                'versioned' => true,
                'extractionStrategy' => 'Laravel Routes',
                'authGuard' => 'api',
                'specialAuthInjector' => TymonJwtTokenInjector::class,
                'globalHeaders' => ['fixed' => [], 'generated' => []],
                'default' => false,
            ],
            $applications[1],
        );
    }
}
