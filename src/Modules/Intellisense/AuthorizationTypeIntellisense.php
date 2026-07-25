<?php

namespace Sunchayn\Nimbus\Modules\Intellisense;

use Illuminate\Support\Str;
use RuntimeException;
use Sunchayn\Nimbus\Modules\Intellisense\Contracts\IntellisenseContract;
use Sunchayn\Nimbus\Modules\Relay\Services\Authorization\Enums\AuthorizationTypeEnum;

/**
 * Generates TypeScript types for authorization types from the backend enum.
 */
class AuthorizationTypeIntellisense implements IntellisenseContract
{
    public const STUB = 'authorization-types.ts.stub';

    public function getTargetFileName(): string
    {
        return Str::remove('.stub', self::STUB);
    }

    public function generate(): string
    {
        $enumCases = [];

        foreach (AuthorizationTypeEnum::cases() as $case) {
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
