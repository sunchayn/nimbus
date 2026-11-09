<?php

declare(strict_types=1);

namespace Sunchayn\Nimbus\Modules\Doctor\Actions;

use Illuminate\Contracts\Config\Repository;
use Illuminate\Contracts\Container\Container;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;
use Sunchayn\Nimbus\Modules\Config\ActiveApplicationResolver;
use Sunchayn\Nimbus\Modules\Doctor\DataTransferObjects\DiagnoseData;
use Sunchayn\Nimbus\Modules\Doctor\DataTransferObjects\DiagnosticCheckData;
use Sunchayn\Nimbus\Modules\Doctor\Enums\DiagnosticStatusEnum;
use Sunchayn\Nimbus\Modules\Doctor\Services\SingleThreadedServerDetector;
use Sunchayn\Nimbus\Modules\Routes\Services\Snapshot\ManifestManager;
use Throwable;

/** @final */
class RunDiagnosticsAction
{
    private const REACHABILITY_TIMEOUT_IN_SECONDS = 3;

    public function __construct(
        private readonly Container $container,
        private readonly Repository $repository,
        private readonly SingleThreadedServerDetector $singleThreadedServerDetector,
    ) {}

    /**
     * @return list<DiagnosticCheckData>
     */
    public function execute(DiagnoseData $data): array
    {
        $diagnosticCheckData = $this->checkSingleThreadedServer($data);

        return [
            $this->checkConfig(),
            $this->checkSnapshotFolder(),
            $this->checkAppKey(),
            $this->checkGuard(),
            $diagnosticCheckData,
            $diagnosticCheckData->status === DiagnosticStatusEnum::Failed
                ? $this->check('Base URL', DiagnosticStatusEnum::Warning, 'Skipped because the server is single threaded.')
                : $this->checkBaseUrl($data),
        ];
    }

    private function checkConfig(): DiagnosticCheckData
    {
        try {
            $resolver = $this->container->make(ActiveApplicationResolver::class);
            $resolver->getRouteExtractionStrategy();
            $resolver->getEnvironments();
        } catch (Throwable $throwable) {
            return $this->check('Config', DiagnosticStatusEnum::Failed, $throwable->getMessage());
        }

        return $this->check('Config', DiagnosticStatusEnum::Ok, 'The configuration values are valid.');
    }

    private function checkSnapshotFolder(): DiagnosticCheckData
    {
        if (! $this->repository->get('nimbus.snapshot', false)) {
            return $this->check('Snapshot folder', DiagnosticStatusEnum::Ok, 'The routes snapshot is disabled.');
        }

        try {
            $directory = $this->container->make(ManifestManager::class)->directory();
        } catch (Throwable $throwable) {
            return $this->check('Snapshot folder', DiagnosticStatusEnum::Failed, $throwable->getMessage());
        }

        // The folder is created on the first save, so the closest existing parent must be writable.
        $existing = $directory;

        while (! is_dir($existing) && dirname($existing) !== $existing) {
            $existing = dirname($existing);
        }

        return is_writable($existing)
            ? $this->check('Snapshot folder', DiagnosticStatusEnum::Ok, $directory.' is writable.')
            : $this->check('Snapshot folder', DiagnosticStatusEnum::Failed, $directory.' is not writable.');
    }

    private function checkAppKey(): DiagnosticCheckData
    {
        return filled($this->repository->get('app.key'))
            ? $this->check('App key', DiagnosticStatusEnum::Ok, 'The app key is set.')
            : $this->check('App key', DiagnosticStatusEnum::Failed, 'The app key is empty. Run php artisan key:generate.');
    }

    private function checkGuard(): DiagnosticCheckData
    {
        try {
            $guard = $this->container->make(ActiveApplicationResolver::class)->getAuthGuard();
        } catch (Throwable $throwable) {
            return $this->check('Guard', DiagnosticStatusEnum::Failed, $throwable->getMessage());
        }

        $guardConfig = $this->repository->get('auth.guards.'.$guard);

        if (! is_array($guardConfig)) {
            return $this->check('Guard', DiagnosticStatusEnum::Failed, sprintf('The guard `%s` is not defined in config/auth.php.', $guard));
        }

        $provider = $guardConfig['provider'] ?? null;

        if ($provider !== null && ! is_array($this->repository->get('auth.providers.'.$provider))) {
            return $this->check('Guard', DiagnosticStatusEnum::Failed, sprintf('The provider `%s` of the guard `%s` is not defined in config/auth.php.', $provider, $guard));
        }

        return $this->check('Guard', DiagnosticStatusEnum::Ok, sprintf('The guard `%s` and its provider are defined.', $guard));
    }

    private function checkSingleThreadedServer(DiagnoseData $data): DiagnosticCheckData
    {
        if (! $this->singleThreadedServerDetector->isSameHostAndPort($data->pageUrl, $data->baseUrl)) {
            return $this->check('Single threaded server', DiagnosticStatusEnum::Ok, 'The base URL points to another host or port.');
        }

        if (! $this->singleThreadedServerDetector->isRunningOnBuiltInServer()) {
            return $this->check('Single threaded server', DiagnosticStatusEnum::Ok, 'The server does not run on the PHP built-in server.');
        }

        return $this->check(
            'Single threaded server',
            DiagnosticStatusEnum::Failed,
            sprintf(
                "Relayed requests will hang. Run a second server and set `nimbus.base_url`.\nSee <a href='%s'>workaround</a>.",
                SingleThreadedServerDetector::GUIDE_URL,
            ),
        );
    }

    private function checkBaseUrl(DiagnoseData $data): DiagnosticCheckData
    {
        try {
            Http::timeout(self::REACHABILITY_TIMEOUT_IN_SECONDS)->withOptions(['verify' => false])->get($data->baseUrl);
        } catch (ConnectionException $connectionException) {
            return $this->check('Base URL', DiagnosticStatusEnum::Failed, sprintf('%s is not reachable. %s', $data->baseUrl, $connectionException->getMessage()));
        }

        return $this->check('Base URL', DiagnosticStatusEnum::Ok, $data->baseUrl.' is reachable.');
    }

    private function check(string $name, DiagnosticStatusEnum $status, string $message): DiagnosticCheckData
    {
        return new DiagnosticCheckData($name, $status, $message);
    }
}
