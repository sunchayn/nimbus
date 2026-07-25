<?php

namespace Sunchayn\Nimbus\Tests\App\Console\Intellisense\Stubs\Providers;

use Illuminate\Support\Str;
use RuntimeException;
use Sunchayn\Nimbus\Modules\Intellisense\Contracts\IntellisenseContract;

class FakeProviderOne implements IntellisenseContract
{
    public function getStub(): string
    {
        return 'fake-provider-one.ts.stub';
    }

    public function getTargetFileName(): string
    {
        /** @phpstan-return non-empty-string */
        return Str::remove('.stub', $this->getStub());
    }

    public function generate(): string
    {
        $enumCases = [];

        foreach (['foo' => 'bar', 'bar' => 'baz'] as $key => $value) {
            $enumCases[] = "    {$key} = '{$value}',";
        }

        $enumContent = implode("\n", $enumCases);

        return $this->replaceStubContent($enumContent);
    }

    private function replaceStubContent(string $enumList): string
    {
        $stubFile = file_get_contents(__DIR__.'/../'.$this->getStub()) ?: throw new RuntimeException('Cannot read stub file.');

        return str_replace('{{ content }}', rtrim($enumList), $stubFile);
    }
}
