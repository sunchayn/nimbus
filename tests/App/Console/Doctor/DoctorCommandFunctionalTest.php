<?php

declare(strict_types=1);

namespace Sunchayn\Nimbus\Tests\App\Console\Doctor;

use PHPUnit\Framework\Attributes\CoversClass;
use Sunchayn\Nimbus\Console\Doctor\DoctorCommand;
use Sunchayn\Nimbus\Modules\Doctor\Actions\RunDiagnosticsAction;
use Sunchayn\Nimbus\Modules\Doctor\DataTransferObjects\DiagnoseData;
use Sunchayn\Nimbus\Modules\Doctor\DataTransferObjects\DiagnosticCheckData;
use Sunchayn\Nimbus\Modules\Doctor\Enums\DiagnosticStatusEnum;
use Sunchayn\Nimbus\Tests\TestCase;

#[CoversClass(DoctorCommand::class)]
class DoctorCommandFunctionalTest extends TestCase
{
    public function test_it_prints_the_checks_and_succeeds(): void
    {
        // Arrange

        config()->set('app.url', 'http://localhost:8000');
        config()->set('nimbus.base_url', 'http://localhost:8001');

        $actionSpy = $this->spy(RunDiagnosticsAction::class);

        // Anticipate

        $actionSpy->shouldReceive('execute')->andReturn([
            new DiagnosticCheckData('Config', DiagnosticStatusEnum::Ok, 'Fine.'),
        ]);

        // Act & Assert

        $this->artisan('nimbus:doctor')
            ->expectsOutput('[OK] Config: Fine.')
            ->assertSuccessful();

        $actionSpy
            ->shouldHaveReceived('execute')
            ->withArgs(function (DiagnoseData $data): bool {
                $this->assertSame('http://localhost:8000', $data->pageUrl);
                $this->assertSame('http://localhost:8001', $data->baseUrl);

                return true;
            });
    }

    public function test_it_replaces_hyperlinks_with_their_url(): void
    {
        // Arrange

        $this->mock(RunDiagnosticsAction::class)
            ->shouldReceive('execute')
            ->andReturn([
                new DiagnosticCheckData(
                    name: 'Server',
                    status: DiagnosticStatusEnum::Warning,
                    message: "Slow.\nSee <a href='https://example.com/guide'>the guide</a>.",
                ),
            ]);

        // Act & Assert

        $this->artisan('nimbus:doctor')
            ->expectsOutput("[WARNING] Server: Slow.\nSee https://example.com/guide.")
            ->assertSuccessful();
    }

    public function test_it_fails_when_a_check_fails(): void
    {
        // Arrange

        $this->mock(RunDiagnosticsAction::class)
            ->shouldReceive('execute')
            ->andReturn([new DiagnosticCheckData('App key', DiagnosticStatusEnum::Failed, 'Empty.')]);

        // Act & Assert

        $this->artisan('nimbus:doctor')->assertFailed();
    }
}
