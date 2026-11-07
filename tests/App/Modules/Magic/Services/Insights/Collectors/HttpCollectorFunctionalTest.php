<?php

namespace Sunchayn\Nimbus\Tests\App\Modules\Magic\Services\Insights\Collectors;

use GuzzleHttp\Exception\ConnectException;
use GuzzleHttp\Psr7\Request as PsrRequest;
use GuzzleHttp\Psr7\Response as PsrResponse;
use GuzzleHttp\TransferStats;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\Events\ResponseReceived;
use Illuminate\Http\Client\Request;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;
use PHPUnit\Framework\Attributes\CoversClass;
use Sunchayn\Nimbus\Modules\Magic\Services\Insights\Collectors\HttpCollector;
use Sunchayn\Nimbus\Modules\Magic\Services\Insights\Support\InsightClock;
use Sunchayn\Nimbus\Tests\TestCase;

#[CoversClass(HttpCollector::class)]
class HttpCollectorFunctionalTest extends TestCase
{
    public function test_it_records_a_call_without_its_query_string(): void
    {
        // Arrange

        Http::fake(['*' => Http::response('created', 201)]);

        $collector = resolve(HttpCollector::class);
        $collector->start();

        // Act

        Http::post('https://example.com/pay?token=secret', ['card' => '4242']);

        $result = $collector->collect();

        // Assert

        $this->assertCount(1, $result->entries);
        $this->assertSame('POST', $result->entries[0]['method']);
        $this->assertSame('https://example.com/pay', $result->entries[0]['url']);
        $this->assertSame(201, $result->entries[0]['status']);
    }

    public function test_it_records_a_failed_connection_without_a_status(): void
    {
        // Arrange

        Http::fake(fn (Request $request) => throw new ConnectException('Connection refused', $request->toPsrRequest()));

        $collector = resolve(HttpCollector::class);
        $collector->start();

        // Act

        try {
            Http::get('https://example.com/down');
        } catch (ConnectionException) {
            // The failure is what the collector records.
        }

        $result = $collector->collect();

        // Assert

        $this->assertCount(1, $result->entries);
        $this->assertNull($result->entries[0]['status']);
        $this->assertNull($result->entries[0]['duration_ms']);
        $this->assertNull($result->entries[0]['offset_ms']);
    }

    public function test_it_records_the_duration_and_start_of_a_call_with_transfer_stats(): void
    {
        // Arrange

        $clock = resolve(InsightClock::class);
        $clock->start();

        usleep(20_000);

        $collector = resolve(HttpCollector::class);
        $collector->start();

        $psrRequest = new PsrRequest('GET', 'https://example.com/slow');
        $psrResponse = new PsrResponse(200);

        $response = new Response($psrResponse);
        $response->transferStats = new TransferStats($psrRequest, $psrResponse, 0.01);

        // Act

        event(new ResponseReceived(new Request($psrRequest), $response));

        $entry = $collector->collect()->entries[0];

        // Assert

        $this->assertSame(10.0, $entry['duration_ms']);
        $this->assertGreaterThanOrEqual(0.0, $entry['offset_ms']);
        $this->assertLessThanOrEqual($clock->elapsedMs() - 10.0 + 0.01, $entry['offset_ms']);
    }

    public function test_it_leaves_the_duration_empty_when_the_response_has_no_transfer_stats(): void
    {
        // Arrange

        Http::fake(['*' => Http::response('ok')]);

        $collector = resolve(HttpCollector::class);
        $collector->start();

        // Act

        Http::get('https://example.com/fake');

        $entry = $collector->collect()->entries[0];

        // Assert

        $this->assertNull($entry['duration_ms']);
        $this->assertNull($entry['offset_ms']);
        $this->assertSame(200, $entry['status']);
    }

    public function test_it_caps_the_entries_and_counts_the_excess(): void
    {
        // Arrange

        Http::fake(['*' => Http::response('ok')]);

        $collector = resolve(HttpCollector::class);
        $collector->start();

        foreach (range(1, HttpCollector::MAX_ENTRIES + 2) as $i) {
            Http::get('https://example.com/'.$i);
        }

        // Act

        $result = $collector->collect();

        // Assert

        $this->assertCount(HttpCollector::MAX_ENTRIES, $result->entries);
        $this->assertSame(2, $result->dropped);
    }
}
