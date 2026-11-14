<?php

namespace Sunchayn\Nimbus\Tests\Integration\Workbench;

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Foundation\Application;
use Illuminate\Routing\Router;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Schema;
use PHPUnit\Framework\Attributes\CoversClass;
use Sunchayn\Nimbus\Modules\Magic\Listeners\InsightCollectorListener;
use Sunchayn\Nimbus\Modules\Magic\Listeners\SandboxModeListener;
use Sunchayn\Nimbus\Modules\Magic\Services\Insights\InsightsService;
use Sunchayn\Nimbus\Modules\Magic\Services\Insights\InsightStore;
use Sunchayn\Nimbus\Tests\TestCase;
use Workbench\App\Events\InventorySynced;
use Workbench\App\Http\Controllers\Demo\InsightController;
use Workbench\App\Jobs\SyncInventoryJob;
use Workbench\App\Mail\OrderReceiptMail;
use Workbench\App\Notifications\OrderReceiptNotification;

#[CoversClass(InsightCollectorListener::class)]
#[CoversClass(InsightsService::class)]
#[CoversClass(InsightController::class)]
#[CoversClass(SandboxModeListener::class)]
class InsightTest extends TestCase
{
    private const ID = 'aaaaaaaa-bbbb-cccc-dddd-eeeeeeeeeeee';

    protected function defineRoutes($router): void
    {
        /** @var Router $router */
        $router->prefix('demo')->group(__DIR__.'/../../../workbench/routes/demo.php');
    }

    protected function setUp(): void
    {
        parent::setUp();

        // The demo route queues a job on the database connection and calls another server.
        Schema::create('jobs', function (Blueprint $table): void {
            $table->id();
            $table->string('queue');
            $table->longText('payload');
            $table->unsignedTinyInteger('attempts');
            $table->unsignedInteger('reserved_at')->nullable();
            $table->unsignedInteger('available_at');
            $table->unsignedInteger('created_at');
        });

        Http::fake(['*' => Http::response(['now' => 'later'])]);
    }

    protected function tearDown(): void
    {
        resolve(InsightStore::class)->pull(self::ID);

        parent::tearDown();
    }

    public function test_it_writes_what_the_request_did_before_the_response_is_returned(): void
    {
        // Act

        $this->getJson('demo/insight/activity', [InsightStore::HEADER => self::ID])->assertOk();

        $insight = resolve(InsightStore::class)->pull(self::ID);

        // Assert

        $this->assertIsArray($insight);
        $this->assertGreaterThanOrEqual(7, count($insight['queries']));
        $this->assertSame('select * from sqlite_master where name = ?', $insight['queries'][0]['sql']);
        $this->assertSame(['table_1'], $insight['queries'][0]['bindings']);
        $this->assertSame('warning', $insight['logs'][0]['level']);
        $this->assertSame('Insight demo warning.', $insight['logs'][0]['message']);
        $this->assertSame(['insight-demo'], array_column($insight['gates'], 'ability'));
        $this->assertFalse($insight['gates'][0]['result']);
        $this->assertContains(['operation' => 'miss', 'key' => 'insight-demo-missing'], $insight['cache']);
        $this->assertContains(['operation' => 'write', 'key' => 'insight-demo'], $insight['cache']);
        $this->assertContains(['operation' => 'hit', 'key' => 'insight-demo'], $insight['cache']);
        $this->assertContains(['operation' => 'forget', 'key' => 'insight-demo'], $insight['cache']);
        $this->assertSame([['name' => InventorySynced::class]], $insight['events']);
        $this->assertSame(SyncInventoryJob::class, $insight['jobs'][0]['job']);
        $this->assertSame('database', $insight['jobs'][0]['connection']);
        $this->assertSame('Your order receipt', $insight['mail'][0]['subject']);

        if (version_compare(Application::VERSION, '11.0.0', '>=')) {
            $this->assertSame(OrderReceiptMail::class, $insight['mail'][0]['mailable']);
        }

        $this->assertSame(OrderReceiptNotification::class, $insight['notifications'][0]['notification']);
        $this->assertSame('mail', $insight['notifications'][0]['channel']);
        $this->assertSame('GET', $insight['http'][0]['method']);
        $this->assertStringEndsWith('/demo/time', $insight['http'][0]['url']);
        $this->assertSame(200, $insight['http'][0]['status']);
        $this->assertGreaterThanOrEqual(100.0, max(array_column($insight['queries'], 'time_ms')));
        $this->assertGreaterThan($insight['queries'][0]['offset_ms'], $insight['queries'][1]['offset_ms']);
        $this->assertNull($insight['exception']);
        $this->assertGreaterThan(0, $insight['peak_memory']);
    }

    public function test_it_captures_the_exception_with_code_frames(): void
    {
        // Act

        $this->getJson('demo/insight/failure', [InsightStore::HEADER => self::ID])->assertStatus(500);

        $insight = resolve(InsightStore::class)->pull(self::ID);

        // Assert

        $this->assertSame(\RuntimeException::class, $insight['exception']['class']);
        $this->assertSame('Insight demo failure.', $insight['exception']['message']);
        $this->assertNotEmpty($insight['exception']['trace'][0]['code']);
    }

    public function test_it_does_nothing_without_the_header(): void
    {
        // Act

        $this->getJson('demo/insight/activity')->assertOk();

        // Assert

        $this->assertNull(resolve(InsightStore::class)->pull(self::ID));
    }

    public function test_it_ignores_an_invalid_id(): void
    {
        // Act

        $this->getJson('demo/insight/activity', [InsightStore::HEADER => '../bad'])->assertOk();

        // Assert

        $this->assertNull(resolve(InsightStore::class)->pull('../bad'));
    }

    public function test_it_captures_mail_notifications_and_jobs_in_sandbox_mode(): void
    {
        // Act

        $this->getJson('demo/insight/sandbox', [
            InsightStore::HEADER => self::ID,
            SandboxModeListener::HEADER => 'mail, notifications, queue',
        ])->assertOk();

        $insight = resolve(InsightStore::class)->pull(self::ID);

        // Assert

        $this->assertIsArray($insight);
        $this->assertSame([], $insight['mail']);
        $this->assertSame('Sandbox demo', $insight['sandbox']['mail'][0]['subject']);
        $this->assertSame(['demo@example.com'], $insight['sandbox']['mail'][0]['to']);
        $this->assertStringContainsString('Hello from the sandbox demo.', $insight['sandbox']['mail'][0]['body']);
        $this->assertSame(['mail'], $insight['sandbox']['notifications'][0]['channels']);
        $this->assertStringEndsWith('@anonymous', $insight['sandbox']['notifications'][0]['notification']);
        $this->assertSame('Illuminate\\Notifications\\AnonymousNotifiable', $insight['sandbox']['notifications'][0]['notifiable']);
        $this->assertSame('default', $insight['sandbox']['jobs'][0]['queue']);
        $this->assertSame('Illuminate\\Queue\\CallQueuedClosure', $insight['sandbox']['jobs'][0]['job']);
    }

    public function test_it_lists_a_sandboxed_kind_that_captured_nothing(): void
    {
        // Act

        $this->getJson('demo/rate-limit', [
            InsightStore::HEADER => self::ID,
            SandboxModeListener::HEADER => 'mail, queue',
        ])->assertOk();

        $insight = resolve(InsightStore::class)->pull(self::ID);

        // Assert

        $this->assertIsArray($insight);
        $this->assertSame(['mail' => [], 'jobs' => []], $insight['sandbox']);
    }

    public function test_it_captures_nothing_without_the_sandbox_header(): void
    {
        // Act

        $this->getJson('demo/insight/activity', [InsightStore::HEADER => self::ID])->assertOk();

        $insight = resolve(InsightStore::class)->pull(self::ID);

        // Assert

        $this->assertIsArray($insight);
        $this->assertSame([], $insight['sandbox']);
    }

    public function test_it_keeps_the_insight_working_when_events_are_faked(): void
    {
        // Act

        $this->getJson('demo/insight/activity', [
            InsightStore::HEADER => self::ID,
            SandboxModeListener::HEADER => 'events',
        ])->assertOk();

        // Assert

        $this->assertIsArray(resolve(InsightStore::class)->pull(self::ID));
    }
}
