<?php

namespace Sunchayn\Nimbus\IntellisenseProviders;

use Illuminate\Support\Str;
use RuntimeException;
use Sunchayn\Nimbus\IntellisenseProviders\Contracts\IntellisenseContract;
use Sunchayn\Nimbus\Modules\Config\Enums\RoutesProcessingStrategyEnum;

/**
 * Generates TypeScript types for route processing strategies from the backend enum.
 */
class RoutesProcessingStrategyIntellisense implements IntellisenseContract
{
    public const STUB = 'routes-processing-strategies.ts.stub';

    public function getTargetFileName(): string
    {
        return Str::remove('.stub', self::STUB);
    }

    public function generate(): string
    {
        $enumCases = [];

        foreach (RoutesProcessingStrategyEnum::cases() as $case) {
            $enumCases[] = sprintf("    %s = '%s',", $case->name, $case->value);
        }

        $enumContent = implode("\n", $enumCases);

        return $this->replaceStubContent($enumContent);
    }

    private function replaceStubContent(string $enumList): string
    {
        $stubFile = file_get_contents(__DIR__.'/stubs/'.self::STUB) ?: throw new RuntimeException('Cannot read stub file.');

        return str_replace('{{ content }}', rtrim($enumList), $stubFile);
    }
}
