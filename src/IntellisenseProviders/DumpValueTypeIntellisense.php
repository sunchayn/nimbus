<?php

namespace Sunchayn\Nimbus\IntellisenseProviders;

use Illuminate\Support\Str;
use RuntimeException;
use Sunchayn\Nimbus\IntellisenseProviders\Contracts\IntellisenseContract;
use Sunchayn\Nimbus\Modules\Relay\Parsers\VarDumpParser\Enums\DumpValueTypeEnum;

/**
 * Generates TypeScript types for dump value types from the backend enum.
 */
class DumpValueTypeIntellisense implements IntellisenseContract
{
    public const STUB = 'dump-value-types.ts.stub';

    public function getTargetFileName(): string
    {
        return Str::remove('.stub', self::STUB);
    }

    public function generate(): string
    {
        $enumCases = [];

        foreach (DumpValueTypeEnum::cases() as $case) {
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
