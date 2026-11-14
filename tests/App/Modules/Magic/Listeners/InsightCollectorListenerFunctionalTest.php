<?php

namespace Sunchayn\Nimbus\Tests\App\Modules\Magic\Listeners;

use Generator;
use Illuminate\Http\Request;
use Illuminate\Routing\Events\RouteMatched;
use Illuminate\Routing\Route;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use Sunchayn\Nimbus\Modules\Magic\Listeners\InsightCollectorListener;
use Sunchayn\Nimbus\Modules\Magic\Services\Insights\InsightsService;
use Sunchayn\Nimbus\Modules\Magic\Services\Insights\InsightStore;
use Sunchayn\Nimbus\Tests\TestCase;

#[CoversClass(InsightCollectorListener::class)]
class InsightCollectorListenerFunctionalTest extends TestCase
{
    private const ID = 'aaaaaaaa-1111-2222-3333-bbbbbbbbbbbb';

    public function test_it_starts_the_service_for_a_valid_id(): void
    {
        // Arrange

        $insightsServiceSpy = $this->spy(InsightsService::class);

        // Act

        $this->dispatchRouteMatched([InsightStore::HEADER => self::ID]);

        // Assert

        $insightsServiceSpy
            ->shouldHaveReceived('start')
            ->once()
            ->withArgs(function (string $id): bool {
                $this->assertSame(self::ID, $id);

                return true;
            });
    }

    #[DataProvider('headersDataProvider')]
    public function test_it_leaves_the_request_alone_without_a_valid_id(array $headers): void
    {
        // Arrange

        $insightsServiceSpy = $this->spy(InsightsService::class);

        // Act

        $this->dispatchRouteMatched($headers);

        // Assert

        $insightsServiceSpy->shouldNotHaveReceived('start');
    }

    public static function headersDataProvider(): Generator
    {
        yield 'no header' => ['headers' => []];

        yield 'empty header' => ['headers' => [InsightStore::HEADER => '']];

        yield 'too short' => ['headers' => [InsightStore::HEADER => 'short']];

        yield 'path traversal' => ['headers' => [InsightStore::HEADER => '../../etc/passwd']];
    }

    /**
     * @param  array<string, string>  $headers
     */
    private function dispatchRouteMatched(array $headers): void
    {
        $request = Request::create('/');

        foreach ($headers as $name => $value) {
            $request->headers->set($name, $value);
        }

        event(new RouteMatched(new Route('GET', '/', fn () => null), $request));
    }
}
