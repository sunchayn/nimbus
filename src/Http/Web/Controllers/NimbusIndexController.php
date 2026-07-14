<?php

namespace Sunchayn\Nimbus\Http\Web\Controllers;

use Illuminate\Contracts\Support\Renderable;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Vite;
use Sunchayn\Nimbus\Modules\Config\ActiveApplicationResolver;
use Sunchayn\Nimbus\Modules\Export\Services\ShareableLinkProcessorService;
use Sunchayn\Nimbus\Modules\Routes\Actions;
use Sunchayn\Nimbus\Modules\Routes\Exceptions\RoutesProcessingException;
use Sunchayn\Nimbus\Modules\Routes\RoutesProcessors\Strategies\RoutesProcessorContract;

class NimbusIndexController
{
    private const VIEW_NAME = 'nimbus::app';

    public function __invoke(
        RoutesProcessorContract $routesProcessorContract,
        Actions\IgnoreRouteErrorAction $ignoreRouteErrorAction,
        Actions\BuildGlobalHeadersAction $buildGlobalHeadersAction,
        Actions\BuildCurrentUserAction $buildCurrentUserAction,
        Actions\DisableThirdPartyUiAction $disableThirdPartyUiAction,
        ActiveApplicationResolver $activeApplicationResolver,
        ShareableLinkProcessorService $shareableLinkProcessorService,
    ): Renderable|RedirectResponse {
        $incomingShareableLinkPayload = $this->processShareableLink($shareableLinkProcessorService, $activeApplicationResolver);

        $this->handleApplicationSwitch();

        $this->handleIgnoreRouteError($ignoreRouteErrorAction);

        $this->configureVite();

        $disableThirdPartyUiAction->execute();

        $baseViewData = [
            'activeApplicationResolver' => $activeApplicationResolver,
            'sharedState' => $incomingShareableLinkPayload,
        ];

        try {
            $routes = $routesProcessorContract->process();

            $viewData = [
                'routes' => $routes->toFrontendArray(),
                'headers' => $buildGlobalHeadersAction->execute(),
                'currentUser' => $buildCurrentUserAction->execute(),
                'primaryProcessorName' => $routesProcessorContract->getName()->value,
                'showOperationId' => $activeApplicationResolver->showOperationId(),
            ];

            return view(self::VIEW_NAME, array_merge($baseViewData, $viewData)); // @phpstan-ignore-line it cannot find the view.
        } catch (RoutesProcessingException $routesProcessingException) {
            return view(self::VIEW_NAME, array_merge($baseViewData, [  // @phpstan-ignore-line it cannot find the view.
                $routesProcessingException->getFrontEndIdentifier() => $routesProcessingException->toArray(),
            ]));
        }
    }

    /**
     * Process shareable link if present in request.
     *
     * @return array<string, mixed>|null
     */
    private function processShareableLink(
        ShareableLinkProcessorService $shareableLinkProcessorService,
        ActiveApplicationResolver $activeApplicationResolver
    ): ?array {
        $shareParam = request()->input('share');

        if (! is_string($shareParam)) {
            return null;
        }

        $shareableLinkProcessorService->process($shareParam);

        $targetApp = $shareableLinkProcessorService->getTargetApplication();

        if ($targetApp && $targetApp !== $activeApplicationResolver->getActiveApplicationKey()) {
            $this->redirectWithApplicationCookie($targetApp);
        }

        return $shareableLinkProcessorService->toFrontendState();
    }

    private function handleApplicationSwitch(): void
    {
        $application = request()->query('application');

        if ($application === null) {
            return;
        }

        $this->redirectWithApplicationCookie($application);
    }

    private function redirectWithApplicationCookie(string $application): never
    {
        abort(
            redirect()
                ->to(request()->fullUrlWithQuery(['application' => null]))
                ->withCookie(cookie()->forever(
                    ActiveApplicationResolver::CURRENT_APPLICATION_COOKIE_NAME,
                    $application
                ))
        );
    }

    private function configureVite(): void
    {
        Vite::useBuildDirectory('vendor/nimbus');
        Vite::useHotFile(base_path('/vendor/sunchayn/nimbus/resources/dist/hot'));
    }

    private function handleIgnoreRouteError(Actions\IgnoreRouteErrorAction $ignoreRouteErrorAction): void
    {
        $ignoreData = request()->input('ignore');

        if ($ignoreData === null) {
            return;
        }

        $ignoreRouteErrorAction->execute(ignoreData: $ignoreData);

        abort(redirect()->to(request()->url()));
    }
}
