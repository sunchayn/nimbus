<?php

namespace Sunchayn\Nimbus\Tests;

use Illuminate\Support\Facades\Http;
use Mockery;
use Orchestra\Testbench\TestCase as BaseTestCase;
use Sunchayn\Nimbus\NimbusServiceProvider;

class TestCase extends BaseTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Http::preventStrayRequests();
    }

    protected function getEnvironmentSetUp($app): void
    {
        $app['config']->set('app.key', 'base64:'.base64_encode(random_bytes(32)));
        $app['config']->set('cache.default', 'array');

        // Only sqlite connections are kept, so a test never waits on a database server that is not there.
        $app['config']->set(
            'database.connections',
            collect($app['config']->get('database.connections'))
                ->filter(static fn (array $connection): bool => $connection['driver'] === 'sqlite')
                ->all(),
        );
        $app['config']->set('nimbus.prefix', 'nimbus');
        $app['config']->set('nimbus.default_application', 'main');
        $app['config']->set('nimbus.applications', [
            'main' => [
                'name' => 'Main Application',
                'routes' => [
                    'prefix' => 'api',
                    'versioned' => false,
                ],
                'headers' => [
                    'x-request-id' => 'uuid',
                    'x-session-id' => 'uuid',
                ],
            ],
            'other' => [
                'name' => 'Other Application',
                'routes' => [
                    'prefix' => 'other-api',
                    'versioned' => true,
                ],
            ],
        ]);
    }

    protected function tearDown(): void
    {
        if ($container = Mockery::getContainer()) {
            $this->addToAssertionCount($container->mockery_getExpectationCount());
        }

        Mockery::close();

        parent::tearDown();
    }

    protected function getPackageProviders($app): array
    {
        return [
            NimbusServiceProvider::class,
        ];
    }
}
