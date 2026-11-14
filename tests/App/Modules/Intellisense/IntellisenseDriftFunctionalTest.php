<?php

namespace Sunchayn\Nimbus\Tests\App\Modules\Intellisense;

use Generator;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use Sunchayn\Nimbus\Console\Intellisense\GenerateIntellisenseCommand;
use Sunchayn\Nimbus\Modules\Intellisense\Contracts\IntellisenseContract;
use Sunchayn\Nimbus\Tests\TestCase;

#[CoversClass(GenerateIntellisenseCommand::class)]
class IntellisenseDriftFunctionalTest extends TestCase
{
    /**
     * @param  class-string<IntellisenseContract>  $provider
     */
    #[DataProvider('providersDataProvider')]
    public function test_it_matches_the_committed_intellisense_file(string $provider): void
    {
        // Arrange

        $command = new GenerateIntellisenseCommand;

        $generator = new $provider;

        $path = dirname(__DIR__, 4).GenerateIntellisenseCommand::TARGET_BASE_PATH.$generator->getTargetFileName();

        // Act

        $expected = $this->withoutDate(invade($command)->getFileHeader().$generator->generate());

        $committed = is_file($path) ? $this->withoutDate((string) file_get_contents($path)) : null;

        // Assert

        $this->assertSame(
            $expected,
            $committed,
            "{$generator->getTargetFileName()} is out of date. Run `php bin/intellisense` and commit the result.",
        );
    }

    public static function providersDataProvider(): Generator
    {
        $providers = invade(new GenerateIntellisenseCommand)->intellisenseProviders;

        foreach ($providers as $provider) {
            yield $provider => ['provider' => $provider];
        }
    }

    private function withoutDate(string $content): string
    {
        return (string) preg_replace('/Generated at: .*/', 'Generated at: {{ date }}.', $content);
    }
}
