<?php

namespace Sunchayn\Nimbus\Tests\App\Console\Intellisense;

use Carbon\CarbonImmutable;
use Mockery;
use PHPUnit\Framework\Attributes\CoversClass;
use RuntimeException;
use Sunchayn\Nimbus\Console\Intellisense\GenerateIntellisenseCommand;
use Sunchayn\Nimbus\Modules\Intellisense\Contracts\IntellisenseContract;
use Sunchayn\Nimbus\Tests\App\Console\Intellisense\Stubs\Providers\FakeProviderOne;
use Sunchayn\Nimbus\Tests\App\Console\Intellisense\Stubs\Providers\FakeProviderTwo;
use Sunchayn\Nimbus\Tests\TestCase;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\StringInput;
use Symfony\Component\Console\Output\OutputInterface;

#[CoversClass(GenerateIntellisenseCommand::class)]
class GenerateIntellisenseCommandFunctionalTest extends TestCase
{
    private const GENERATED_BASE_PATH = __DIR__.'/../../../../resources/js/interfaces/generated';

    protected function setUp(): void
    {
        parent::setUp();

        CarbonImmutable::setTestNow(CarbonImmutable::now());
    }

    protected function tearDown(): void
    {
        @unlink(self::GENERATED_BASE_PATH.'/fake-provider-one.ts');
        @unlink(self::GENERATED_BASE_PATH.'/fake-provider-two.ts');

        parent::tearDown();
    }

    public function test_it_generates_intellisense_properly(): void
    {
        // Arrange

        $command = resolve(GenerateIntellisenseCommand::class);

        invade($command)->intellisenseProviders = [
            FakeProviderOne::class,
            FakeProviderTwo::class,
        ];

        $input = Mockery::mock(StringInput::class)->makePartial();
        $output = Mockery::mock(OutputInterface::class);

        // <helper> Uncomment for debug
        // $output->shouldReceive('writeln')->withAnyArgs()->andReturnUsing(fn ($values) => dump($values));

        // Anticipate

        $output->shouldReceive('write')->once();
        $output->shouldReceive('writeln')->with("\n")->once();
        $output->shouldReceive('writeln')->with(' >> <info>Generating TypeScript Intellisense...</info>')->once();
        $output->shouldReceive('writeln')->with("\n> Generating fake-provider-one.ts")->once();
        $output->shouldReceive('writeln')->with('<info>✓ Generated.</info>')->once();
        $output->shouldReceive('writeln')->with("\n> Generating fake-provider-two.ts")->once();
        $output->shouldReceive('writeln')->with('<info>✓ Generated.</info>')->once();
        $output->shouldReceive('writeln')->with("\n<info>✓ All Intellisense generated successfully!</info>")->once();

        // Act

        $result = $command->run($input, $output);

        // Assert

        $this->assertEquals(Command::SUCCESS, $result);

        $this->assertEquals(
            $this->getExpectedContentForProviderOne(),
            file_get_contents(self::GENERATED_BASE_PATH.'/fake-provider-one.ts'),
        );

        $this->assertEquals(
            $this->getExpectedContentForProviderTwo(),
            file_get_contents(self::GENERATED_BASE_PATH.'/fake-provider-two.ts'),
        );
    }

    public function test_it_handles_intellisense_generation_failure(): void
    {
        // Arrange

        $command = resolve(GenerateIntellisenseCommand::class);

        $input = Mockery::mock(StringInput::class)->makePartial();
        $output = Mockery::mock(OutputInterface::class);

        $failingIntellisenseDummy = new class implements IntellisenseContract
        {
            public function getTargetFileName(): string
            {
                return 'test.ts';
            }

            public function generate(): string
            {
                throw new RuntimeException('Generation failed');
            }
        };

        invade($command)->intellisenseProviders = [
            $failingIntellisenseDummy::class,
        ];

        // Anticipate

        $output->shouldReceive('write')->once();
        $output->shouldReceive('writeln')->with("\n")->once();
        $output->shouldReceive('writeln')->with(' >> <info>Generating TypeScript Intellisense...</info>')->once();
        $output->shouldReceive('writeln')->with("\n> Generating test.ts")->once();
        $output->shouldReceive('writeln')->with('')->once();
        $output->shouldReceive('writeln')->with('<error>Failed to generate:</error>.')->once();
        $output->shouldReceive('writeln')->with('Generation failed')->once();
        $output->shouldReceive('writeln')->with('')->once();

        // Act

        $result = $command->run($input, $output);

        // Assert

        $this->assertEquals(Command::FAILURE, $result);
    }

    /*
     * Helpers.
     */

    private function getExpectedContentForProviderOne(): string
    {
        $datetime = CarbonImmutable::now()->toIso8601String();

        return <<<EXPECTED
/*
 * This file is auto-generated.
 * Don't update it manually, otherwise, your changes will be lost.
 * To update the file run `php bin/intellisense`.
 *
 * Generated at: $datetime.
 */

type FakeProviderOne = string;

enum FakeOneValues {
    foo = 'bar',
    bar = 'baz',
}

EXPECTED;
    }

    private function getExpectedContentForProviderTwo(): string
    {
        $datetime = CarbonImmutable::now()->toIso8601String();

        return <<<EXPECTED
/*
 * This file is auto-generated.
 * Don't update it manually, otherwise, your changes will be lost.
 * To update the file run `php bin/intellisense`.
 *
 * Generated at: $datetime.
 */

enum FakeTwoValues {
    foo = 'bar',
    bar = 'baz',
}

type FakeProviderTwo = number;

EXPECTED;
    }
}
