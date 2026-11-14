<?php

declare(strict_types=1);

namespace Sunchayn\Nimbus\Tests\App\Modules\Doctor\Actions;

use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;
use PHPUnit\Framework\Attributes\CoversClass;
use Sunchayn\Nimbus\Modules\Doctor\Actions\RunDiagnosticsAction;
use Sunchayn\Nimbus\Modules\Doctor\DataTransferObjects\DiagnoseData;
use Sunchayn\Nimbus\Modules\Doctor\DataTransferObjects\DiagnosticCheckData;
use Sunchayn\Nimbus\Modules\Doctor\Enums\DiagnosticStatusEnum;
use Sunchayn\Nimbus\Modules\Doctor\Services\SingleThreadedServerDetector;
use Sunchayn\Nimbus\Tests\TestCase;

#[CoversClass(RunDiagnosticsAction::class)]
#[CoversClass(DiagnosticCheckData::class)]
class RunDiagnosticsActionFunctionalTest extends TestCase
{
    public function test_it_passes_on_a_healthy_setup(): void
    {
        // Arrange

        Http::fake(['*' => Http::response('ok')]);

        $action = resolve(RunDiagnosticsAction::class);

        // Act

        $checks = $action->execute(new DiagnoseData('http://localhost:8000', 'http://localhost:8001'));

        // Assert

        $this->assertSame(
            [DiagnosticStatusEnum::Ok],
            array_values(array_unique(array_map(fn (DiagnosticCheckData $check): DiagnosticStatusEnum => $check->status, $checks), SORT_REGULAR)),
        );
    }

    public function test_it_reports_a_missing_app_key_and_an_unknown_guard(): void
    {
        // Arrange

        Http::fake(['*' => Http::response('ok')]);

        config()->set('app.key', '');
        config()->set('nimbus.applications.main.auth.guard', 'missing');

        $action = resolve(RunDiagnosticsAction::class);

        // Act

        $checks = $action->execute(new DiagnoseData('http://localhost:8000', 'http://localhost:8001'));

        // Assert

        $failed = collect($checks)
            ->filter(fn (DiagnosticCheckData $check): bool => $check->status === DiagnosticStatusEnum::Failed)
            ->map(fn (DiagnosticCheckData $check): string => $check->name)
            ->values()
            ->all();

        $this->assertSame(['App key', 'Guard'], $failed);
    }

    public function test_it_reports_an_invalid_config(): void
    {
        // Arrange

        Http::fake(['*' => Http::response('ok')]);

        config()->set('nimbus.environments', ['staging' => ['name' => 'Staging', 'base_url' => 'nope']]);

        $action = resolve(RunDiagnosticsAction::class);

        // Act

        $checks = $action->execute(new DiagnoseData('http://localhost:8000', 'http://localhost:8001'));

        // Assert

        $this->assertSame(DiagnosticStatusEnum::Failed, $checks[0]->status);
    }

    public function test_it_reports_an_unreachable_base_url(): void
    {
        // Arrange

        Http::fake(['*' => fn () => throw new ConnectionException('Connection refused')]);

        $action = resolve(RunDiagnosticsAction::class);

        // Act

        $checks = $action->execute(new DiagnoseData('http://localhost:8000', 'http://localhost:8001'));

        // Assert

        $this->assertSame(DiagnosticStatusEnum::Failed, $checks[5]->status);
        $this->assertStringContainsString('Connection refused', $checks[5]->message);
    }

    public function test_it_reports_a_single_threaded_server(): void
    {
        // Arrange

        $this->mock(SingleThreadedServerDetector::class)
            ->makePartial()
            ->shouldReceive('isRunningOnBuiltInServer')
            ->andReturn(true);

        $action = resolve(RunDiagnosticsAction::class);

        // Act

        $checks = $action->execute(new DiagnoseData('http://localhost:8000', 'http://localhost:8000'));

        // Assert

        $this->assertSame(DiagnosticStatusEnum::Failed, $checks[4]->status);
        $this->assertStringContainsString('nimbus.base_url', $checks[4]->message);
        $this->assertStringContainsString(SingleThreadedServerDetector::GUIDE_URL, $checks[4]->message);
        $this->assertSame(DiagnosticStatusEnum::Warning, $checks[5]->status);
    }

    public function test_it_checks_the_snapshot_folder_when_enabled(): void
    {
        // Arrange

        Http::fake(['*' => Http::response('ok')]);

        config()->set('nimbus.snapshot', true);

        $action = resolve(RunDiagnosticsAction::class);

        // Act

        $checks = $action->execute(new DiagnoseData('http://localhost:8000', 'http://localhost:8001'));

        // Assert

        $this->assertSame(DiagnosticStatusEnum::Ok, $checks[1]->status);
        $this->assertStringContainsString('is writable', $checks[1]->message);
    }
}
