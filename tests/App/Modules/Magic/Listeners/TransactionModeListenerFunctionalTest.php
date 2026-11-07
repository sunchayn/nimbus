<?php

namespace Sunchayn\Nimbus\Tests\App\Modules\Magic\Listeners;

use Illuminate\Foundation\Http\Events\RequestHandled;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Routing\Events\RouteMatched;
use Illuminate\Routing\Route;
use Illuminate\Support\Facades\DB;
use PHPUnit\Framework\Attributes\CoversClass;
use Sunchayn\Nimbus\Modules\Magic\Listeners\TransactionModeListener;
use Sunchayn\Nimbus\Tests\TestCase;

#[CoversClass(TransactionModeListener::class)]
class TransactionModeListenerFunctionalTest extends TestCase
{
    protected function defineEnvironment($app): void
    {
        parent::defineEnvironment($app);

        $app['config']->set('database.connections.first', ['driver' => 'sqlite', 'database' => ':memory:']);
        $app['config']->set('database.connections.second', ['driver' => 'sqlite', 'database' => ':memory:']);
        $app['config']->set('database.connections.broken', ['driver' => 'sqlite', 'database' => '/nimbus/missing/db.sqlite']);
    }

    public function test_it_opens_a_transaction_on_every_reachable_connection(): void
    {
        // Act

        $this->dispatchRouteMatched(transactionMode: true);

        // Assert

        $this->assertSame(1, DB::connection('first')->transactionLevel());
        $this->assertSame(1, DB::connection('second')->transactionLevel());
    }

    public function test_it_rolls_back_every_connection_when_the_app_terminates(): void
    {
        // Arrange

        $this->dispatchRouteMatched(transactionMode: true);

        // Act

        $this->app->terminate();

        // Assert

        $this->assertSame(0, DB::connection('first')->transactionLevel());
        $this->assertSame(0, DB::connection('second')->transactionLevel());
    }

    public function test_it_reports_connections_that_could_not_be_covered(): void
    {
        // Arrange

        $this->dispatchRouteMatched(transactionMode: true);

        $response = new Response('ok');

        // Act

        $this->app['events']->dispatch(new RequestHandled(Request::create('/test'), $response));

        // Assert

        $skipped = explode(', ', $response->headers->get(TransactionModeListener::SKIPPED_HEADER));

        $this->assertContains('broken', $skipped);
        $this->assertNotContains('first', $skipped);
        $this->assertNotContains('second', $skipped);
    }

    public function test_it_does_nothing_when_the_header_is_missing(): void
    {
        // Arrange

        $this->dispatchRouteMatched(transactionMode: false);

        $response = new Response('ok');

        // Act

        $this->app['events']->dispatch(new RequestHandled(Request::create('/test'), $response));

        // Assert

        $this->assertSame(0, DB::connection('first')->transactionLevel());
        $this->assertFalse($response->headers->has(TransactionModeListener::SKIPPED_HEADER));
    }

    private function dispatchRouteMatched(bool $transactionMode): void
    {
        $request = Request::create('/test', 'GET');

        if ($transactionMode) {
            $request->headers->set('X-Nimbus-Transaction-Mode', '1');
        }

        $this->app['events']->dispatch(new RouteMatched(new Route(['GET'], '/test', fn () => 'ok'), $request));
    }
}
