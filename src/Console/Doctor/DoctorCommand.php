<?php

declare(strict_types=1);

namespace Sunchayn\Nimbus\Console\Doctor;

use Illuminate\Console\Command;
use Illuminate\Contracts\Config\Repository;
use Sunchayn\Nimbus\Modules\Doctor\Actions\RunDiagnosticsAction;
use Sunchayn\Nimbus\Modules\Doctor\DataTransferObjects\DiagnoseData;
use Sunchayn\Nimbus\Modules\Doctor\Enums\DiagnosticStatusEnum;

class DoctorCommand extends Command
{
    protected $signature = 'nimbus:doctor';

    protected $description = 'Check that Nimbus is configured correctly.';

    public function handle(RunDiagnosticsAction $runDiagnosticsAction, Repository $config): int
    {
        $pageUrl = (string) $config->get('app.url');

        $checks = $runDiagnosticsAction->execute(
            data: new DiagnoseData(
                pageUrl: $pageUrl,
                baseUrl: (string) ($config->get('nimbus.base_url') ?? $pageUrl),
            ),
        );

        $hasFailure = false;

        foreach ($checks as $check) {
            $hasFailure = $hasFailure || $check->status === DiagnosticStatusEnum::Failed;

            $this->line(
                sprintf(
                    '<fg=%s>[%s]</> %s: %s',
                    $this->colorFor($check->status),
                    strtoupper($check->status->value),
                    $check->name,
                    $this->replaceHyperlinks($check->message),
                ),
            );
        }

        return $hasFailure ? self::FAILURE : self::SUCCESS;
    }

    private function colorFor(DiagnosticStatusEnum $status): string
    {
        return match ($status) {
            DiagnosticStatusEnum::Ok => 'green',
            DiagnosticStatusEnum::Warning => 'yellow',
            DiagnosticStatusEnum::Failed => 'red',
        };
    }

    private function replaceHyperlinks(string $message): string
    {
        return (string) preg_replace(
            pattern: '/<a\s+href=([\'"])(.*?)\1[^>]*>.*?<\/a>/is',
            replacement: '<fg=blue;options=underscore>$2</>',
            subject: $message,
        );
    }
}
