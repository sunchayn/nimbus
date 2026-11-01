<?php

namespace Sunchayn\Nimbus\Modules\Intellisense;

use Illuminate\Support\Str;
use RuntimeException;
use Sunchayn\Nimbus\Modules\Config\Enums\EnvironmentSourceEnum;
use Sunchayn\Nimbus\Modules\Intellisense\Contracts\IntellisenseContract;

/**
 * Generates TypeScript types for environment sources from the backend enum.
 */
class EnvironmentSourceIntellisense implements IntellisenseContract
{
    public const STUB = 'environment-sources.ts.stub';

    public function getTargetFileName(): string
    {
        return Str::remove('.stub', self::STUB);
    }

    public function generate(): string
    {
        $enumCases = [];

        foreach (EnvironmentSourceEnum::cases() as $case) {
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
