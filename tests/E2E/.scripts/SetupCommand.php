<?php

declare(strict_types=1);

use Illuminate\Filesystem\Filesystem;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

use function Laravel\Prompts\error;
use function Laravel\Prompts\info;
use function Laravel\Prompts\intro;
use function Laravel\Prompts\note;
use function Laravel\Prompts\outro;
use function Laravel\Prompts\warning;

/**
 * Note: this script is intended for the CI job.
 *
 * Local usage:
 *   - Ensure the current branch is up to date with remote, OR
 *   - Skip running setup.sh and directly use the launch script with a local dev repository
 *     e.g. `php ./tests/E2E/.scripts/bin launch --workdir=../../../nimbus-dev`
 */
class SetupCommand extends Command
{
    private const PACKAGE_NAME = 'sunchayn/nimbus';

    private const DEV_REPO_URL = 'https://github.com/sunchayn/nimbus-dev.git';

    private const E2E_RELATIVE_DIR = __DIR__.'/..';

    private const ROOT_RELATIVE_DIR = __DIR__.'/../../..';

    private string $e2eAbsolutePath;

    private string $workdirAbsolutePath;

    private string $rootAbsolutePath;

    private Filesystem $filesystem;

    protected function configure(): void
    {
        $this
            ->setName('setup')
            ->setDescription('Setup the E2E test project and environment.')
            ->addArgument(
                name: 'branch-name',
                mode: InputArgument::REQUIRED,
                description: 'The branch to install.',
            )
            ->addArgument(
                name: 'repo-url',
                mode: InputArgument::OPTIONAL,
                description: 'VCS URL of the repository (required for forks).',
            );
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $this->filesystem = new Filesystem;

        $this->e2eAbsolutePath = (string) realpath(self::E2E_RELATIVE_DIR);
        $this->workdirAbsolutePath = $this->e2eAbsolutePath.'/.workdir';
        $this->rootAbsolutePath = (string) realpath(self::ROOT_RELATIVE_DIR);

        /** @var string $branchName */
        $branchName = $input->getArgument('branch-name');
        $repoUrl = (string) ($input->getArgument('repo-url') ?? '');

        intro('E2E Setup');

        info('Branch: '.$branchName);

        if ($repoUrl !== '') {
            info('Repository: '.$repoUrl);
        }

        try {
            $this->deleteExistingWorkdir();

            $this->cloneDevRepoIntoWorkdir();

            $this->targetCurrentBranchInComposer($branchName, $repoUrl);

            chdir($this->workdirAbsolutePath);

            $this->installComposerPackageFromBranch();

            $this->installNodeDependencies();

            $this->copyEnvTemplate();

            $this->prepareDatabase();

            chdir($this->rootAbsolutePath);

            $this->buildAssetsIntoWorkdir();
        } catch (Throwable $throwable) {
            error($throwable->getMessage());

            return self::FAILURE;
        }

        outro('Setup complete. Ready for E2E tests.');

        return self::SUCCESS;
    }

    private function deleteExistingWorkdir(): void
    {
        note('Resetting working directory...');

        if ($this->filesystem->isDirectory($this->workdirAbsolutePath)) {
            $this->filesystem->deleteDirectory($this->workdirAbsolutePath);
        }
    }

    private function cloneDevRepoIntoWorkdir(): void
    {
        info('Cloning Dev repository...');

        $this->runExternalCommand(
            command: sprintf('git clone %s %s', self::DEV_REPO_URL, $this->workdirAbsolutePath),
            errorMessageOnFailure: 'Failed to clone the dev repository.',
        );
    }

    /**
     * @throws \Illuminate\Contracts\Filesystem\FileNotFoundException
     * @throws JsonException
     */
    private function targetCurrentBranchInComposer(
        string $branchName,
        string $repoUrl,
    ): void {
        $composerFilePath = $this->workdirAbsolutePath.'/composer.json';

        note('Patching composer.json...');

        if (! $this->filesystem->exists($composerFilePath)) {
            throw new RuntimeException('composer.json not found in .workdir.');
        }

        $composerJson = json_decode(
            $this->filesystem->get($composerFilePath),
            true,
            flags: JSON_THROW_ON_ERROR,
        );

        $composerJson['repositories'] ??= [];

        if ($repoUrl !== '') {
            array_unshift(
                $composerJson['repositories'],
                [
                    'type' => 'vcs',
                    'url' => $repoUrl,
                ],
            );
        }

        $composerJson['require'][self::PACKAGE_NAME] = 'dev-'.$branchName;

        $this->filesystem->put(
            $composerFilePath,
            json_encode(
                $composerJson,
                JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES,
            ).PHP_EOL,
        );

        note('composer.json updated successfully.');
    }

    private function installComposerPackageFromBranch(): void
    {
        info('Installing/updating the package via Composer...');

        $this->runExternalCommand(
            command: 'composer update '.self::PACKAGE_NAME.' --no-progress --ansi',
            errorMessageOnFailure: 'Failed to install/update the package via Composer.',
        );
    }

    private function installNodeDependencies(): void
    {
        info('Installing Node.js dependencies...');

        $this->runExternalCommand(
            command: 'npm ci --ignore-scripts',
            errorMessageOnFailure: 'Failed to install Node.js dependencies.',
        );
    }

    private function copyEnvTemplate(): void
    {
        note('Setting up environment file...');

        $this->filesystem->delete($this->workdirAbsolutePath.'/.env');

        $this->filesystem->copy($this->e2eAbsolutePath.'/.env.template', $this->workdirAbsolutePath.'/.env');
    }

    private function prepareDatabase(): void
    {
        note('Preparing database...');

        $this->filesystem->put($this->workdirAbsolutePath.'/database/database.sqlite', '');

        $this->runMigrations();
    }

    private function runMigrations(): void
    {
        info('Running migrations...');

        $this->runExternalCommand(
            command: 'php artisan migrate --force',
            errorMessageOnFailure: 'Failed to run database migrations.',
        );
    }

    private function buildDevAssets(): void
    {
        info('Building dev assets...');

        $this->runExternalCommand(
            command: 'npm run build:dev',
            errorMessageOnFailure: 'Failed to build dev assets.',
        );
    }

    private function buildAssetsIntoWorkdir(): void
    {
        $this->buildDevAssets();

        note('Publishing assets to workdir...');

        $this->filesystem->copyDirectory(
            $this->rootAbsolutePath.'/resources/dist',
            $this->workdirAbsolutePath.'/public/vendor/nimbus',
        );
    }

    /*
     * Helpers.
     */

    /**
     * @throws RuntimeException
     */
    private function runExternalCommand(string $command, string $errorMessageOnFailure): void
    {
        warning('>> '.$command);

        passthru($command, $resultCode);

        if ($resultCode !== 0) {
            throw new RuntimeException($errorMessageOnFailure);
        }
    }
}
