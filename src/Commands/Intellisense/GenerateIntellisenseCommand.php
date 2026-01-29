<?php

namespace Sunchayn\Nimbus\Commands\Intellisense;

use Carbon\CarbonImmutable;
use Illuminate\Support\Str;
use RuntimeException;
use Sunchayn\Nimbus\IntellisenseProviders;
use Sunchayn\Nimbus\IntellisenseProviders\Contracts\IntellisenseContract;
use Symfony\Component\Console\Command\Command as SymfonyCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Throwable;

/**
 * Generates TypeScript intellisense files for the BE centric features.
 */
class GenerateIntellisenseCommand extends SymfonyCommand
{
    /** @var array<int, class-string<IntellisenseContract>> */
    protected array $intellisenseProviders = [
        IntellisenseProviders\AuthorizationTypeIntellisense::class,
        IntellisenseProviders\RandomValueGeneratorIntellisense::class,
        IntellisenseProviders\DumpValueTypeIntellisense::class,
        IntellisenseProviders\RoutesProcessingStrategyIntellisense::class,
    ];

    const TARGET_BASE_PATH = '/resources/js/interfaces/generated/';

    /**
     * Configures the command with its name, description, and options.
     */
    protected function configure(): void
    {
        $this
            ->setName('generate-intellisense')
            ->setDescription('Generate TypeScript intellisense files from Laravel enums and configurations to resources/js/interfaces/generated/');
    }

    /**
     * Executes the intellisense generation process.
     *
     * Processes each registered intellisense generator and creates
     * the corresponding TypeScript files in the target directory.
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $this->printHeader($output);

        $output->writeln(' >> <info>Generating TypeScript Intellisense...</info>');

        foreach ($this->intellisenseProviders as $intellisenseProvider) {
            /** @var IntellisenseContract $instance */
            $instance = new $intellisenseProvider;

            $output->writeln('
> Generating '.$instance->getTargetFileName());

            try {
                $content = $this->getFileHeader().$instance->generate();

                $targetPath = $this->getTargetPath($instance->getTargetFileName());

                $this->createDirectoryIfItDoesntExist(dirname($targetPath));

                file_put_contents($targetPath, $content);

                $output->writeln('<info>✓ Generated.</info>');
            } catch (Throwable $throwable) {
                $output->writeln('');
                $output->writeln('<error>Failed to generate:</error>.');
                $output->writeln($throwable->getMessage());
                $output->writeln('');

                return self::FAILURE;
            }
        }

        $output->writeln("\n<info>✓ All Intellisense generated successfully!</info>");

        return self::SUCCESS;
    }

    /**
     * Constructs the target path for the generated file.
     *
     * Files are generated in the resources/js/interfaces/generated/ directory
     * to maintain organization and avoid conflicts with other generated files.
     */
    private function getTargetPath(string $filename): string
    {
        return getcwd().self::TARGET_BASE_PATH.$filename;
    }

    /**
     * Ensures the target directory exists before writing files.
     */
    private function createDirectoryIfItDoesntExist(string $directory): void
    {
        if (is_dir($directory)) {
            return;
        }

        mkdir($directory, 0755, true);
    }

    private function getFileHeader(): string
    {
        return Str::replace(
            '{{ date }}',
            CarbonImmutable::now()->toIso8601String(),
            file_get_contents(__DIR__.'/stubs/intellisense-header.stub') ?: throw new RuntimeException('Cannot load header stub.'),
        );
    }

    /*
     * UI Helpers.
     */

    private function printHeader(OutputInterface $output): void
    {
        $output->write(<<<HEAD
  _   _ _           _                 ___       _       _ _ _ ____
 | \ | (_)_ __ ___ | |__  _   _ ___  |_ _|_ __ | |_ ___| | (_) ___|  ___ _ __  ___  ___
 |  \| | | '_ ` _ \| '_ \| | | / __|  | || '_ \| __/ _ \ | | \___ \ / _ \ '_ \/ __|/ _ \
 | |\  | | | | | | | |_) | |_| \__ \  | || | | | ||  __/ | | |___) |  __/ | | \__ \  __/
 |_| \_|_|_| |_| |_|_.__/ \__,_|___/ |___|_| |_|\__\___|_|_|_|____/ \___|_| |_|___/\___|
HEAD);
        $output->writeln("\n");
    }
}
