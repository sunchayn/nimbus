<?php

declare(strict_types=1);

use Illuminate\Filesystem\Filesystem;
use Illuminate\Process\Factory as ProcessFactory;
use Illuminate\Process\InvokedProcess;
use Illuminate\Process\PendingProcess;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

use function Laravel\Prompts\error;
use function Laravel\Prompts\info;
use function Laravel\Prompts\intro;
use function Laravel\Prompts\spin;
use function Laravel\Prompts\warning;

class LaunchCommand extends Command
{
    private const SERVER_PROTOCOL = 'http';

    private const SERVER_HOST = '127.0.0.1';

    private Filesystem $filesystem;

    /**
     * @var (ProcessFactory&PendingProcess)|null
     */
    private ?ProcessFactory $processFactory = null;

    private const SCRIPT_DIR = __DIR__.'/..';

    private const WAITING_FOR_SERVER_TIMEOUT_IN_SECONDS = 15;

    /** @var InvokedProcess[] */
    private array $processes = [];

    private string $workdir;

    protected function configure(): void
    {
        $this
            ->setName('launch')
            ->setDescription('Start E2E test servers and configure the environment.')
            ->addOption(
                name: 'workdir',
                mode: InputOption::VALUE_OPTIONAL,
                description: 'Path to the work directory (relative to tests/E2E/ or absolute).',
                default: '.workdir',
            )
            ->addOption(
                'port1',
                mode: InputOption::VALUE_OPTIONAL,
                description: 'Port for the main PHP server.',
                default: '8000',
            )
            ->addOption(
                'port2',
                mode: InputOption::VALUE_OPTIONAL,
                description: 'Port for the secondary PHP server.',
                default: '8001',
            );
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $this->filesystem = new Filesystem;
        $this->processFactory = new ProcessFactory;

        $port1 = (int) $input->getOption('port1');
        $port2 = (int) $input->getOption('port2');

        intro('E2E — Launch');

        $workdir = $this->resolveTargetDir(
            workdir: (string) $input->getOption('workdir'),
        );

        if ($workdir === null) {
            error('Work directory not found: '.$workdir);

            return self::FAILURE;
        }

        $this->workdir = $workdir;

        try {
            $this->launchServerOn(port: $port1, logFile: 'php_server1.log');
            $this->launchServerOn(port: $port2, logFile: 'php_server2.log');

            $this->configureEnvironment($port1, $port2);

            $this->pingServerOn(port: $port1);
            $this->pingServerOn(port: $port2);
        } catch (Throwable $throwable) {
            error($throwable->getMessage());

            $this->stopServers();

            return self::FAILURE;
        }

        warning("\nServers are running. Press Ctrl+C to stop.");

        while (true) { // @phpstan-ignore-line
            sleep(5);
        }
    }

    private function resolveTargetDir(string $workdir): ?string
    {
        $path = str_starts_with($workdir, '/')
            ? $workdir
            : self::SCRIPT_DIR.'/'.$workdir;

        $resolved = realpath($path);

        return ($resolved !== false && is_dir($resolved))
            ? $resolved
            : null;
    }

    private function launchServerOn(
        int $port,
        string $logFile,
    ): void {
        $url = $this->craftUrlFor(port: $port);

        spin(
            function () use ($logFile, $port): bool {
                $portStatusOutput = shell_exec('lsof -i:'.$port);

                if ($portStatusOutput !== null) {
                    throw new RuntimeException(
                        "Failed to launch server on port #{$port}.\nPort is already used.",
                    );
                }

                $this->processes[] = $this // @phpstan-ignore-line
                    ->processFactory
                    ->path($this->workdir)
                    ->env(['PHP_CLI_SERVER_WORKERS' => '8'])
                    ->start(
                        command: sprintf(
                            'php artisan serve --host=%s --port=%d > %s 2>&1 &',
                            self::SERVER_HOST,
                            $port,
                            $this->workdir.'/'.$logFile,
                        ),
                    );

                return true;
            },
            sprintf('Starting PHP server on port %d', $port),
        );

        info('Server started at '.$url);
    }

    private function pingServerOn(int $port): void
    {
        $url = $this->craftUrlFor(port: $port);

        spin(
            fn () => $this->waitForUrl($url),
            message: sprintf('Checking server health for port:%d', $port),
        );

        info('Server ready at '.$url);
    }

    private function configureEnvironment(int $port1, int $port2): void
    {
        spin(
            function () use ($port1, $port2): bool {
                $this->addEnvVariable(key: 'APP_URL', value: $this->craftUrlFor(port: $port1));
                $this->addEnvVariable(key: 'NIMBUS_RELAY_ENDPOINT', value: $this->craftUrlFor(port: $port2));
                $this->addEnvVariable(key: 'CACHE_STORE', value: 'array');

                return true;
            },
            'Configuring Environment',
        );

        info('Environment configured.');
    }

    /*
     * Helpers.
     */

    private function waitForUrl(string $url): void
    {
        $elapsed = 0;

        while (true) {
            try {
                $response = (new Illuminate\Http\Client\Factory)
                    ->connectTimeout(1)
                    ->timeout(2)
                    ->get($url);

                if ($response->successful()) {
                    return;
                }
            } catch (Throwable $e) {
                // Ignore and retry.
            }

            sleep(1);

            $elapsed++;

            if ($elapsed >= self::WAITING_FOR_SERVER_TIMEOUT_IN_SECONDS) {
                throw new RuntimeException(
                    "Failed to launch server at <{$url}>.\nTimed out waiting for health signal after ".self::WAITING_FOR_SERVER_TIMEOUT_IN_SECONDS.' seconds.',
                );
            }
        }
    }

    private function craftUrlFor(int $port): string
    {
        return self::SERVER_PROTOCOL.'://'.self::SERVER_HOST.':'.$port;
    }

    private function stopServers(): void
    {
        if ($this->processes === []) {
            return;
        }

        info('Stopping servers...');

        foreach ($this->processes as $process) {
            $process->stop();
        }

        warning('Done!');
    }

    private function addEnvVariable(string $key, string $value): void
    {
        $envFile = $this->workdir.'/.env';

        $content = $this->filesystem->get($envFile);

        // Remove existing definition of the key
        $content = (string) preg_replace("/^{$key}=.*\n/m", '', $content);

        $content .= sprintf('%s="%s"', $key, $value).PHP_EOL;

        $this->filesystem->put($envFile, $content);
    }
}
