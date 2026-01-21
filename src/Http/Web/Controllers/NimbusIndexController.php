<?php

namespace Sunchayn\Nimbus\Http\Web\Controllers;

use Illuminate\Contracts\Support\Renderable;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Route as RouteFacade;
use Illuminate\Support\Facades\Vite;
use Illuminate\Support\Str;
use Sunchayn\Nimbus\Modules\Config\ActiveApplicationResolver;
use Sunchayn\Nimbus\Modules\Export\Services\ShareableLinkProcessorService;
use Sunchayn\Nimbus\Modules\Routes\Actions;
use Sunchayn\Nimbus\Modules\Routes\Exceptions\RouteExtractionException;

class NimbusIndexController
{
    private const VIEW_NAME = 'nimbus::app';

    public function __invoke(
        Actions\ExtractRoutesAction $extractRoutesAction,
        Actions\IgnoreRouteErrorAction $ignoreRouteErrorAction,
        Actions\BuildGlobalHeadersAction $buildGlobalHeadersAction,
        Actions\BuildCurrentUserAction $buildCurrentUserAction,
        Actions\DisableThirdPartyUiAction $disableThirdPartyUiAction,
        ActiveApplicationResolver $activeApplicationResolver,
        ShareableLinkProcessorService $shareableLinkProcessor,
    ): Renderable|RedirectResponse {
        $incomingShareableLinkPayload = $this->processShareableLink($shareableLinkProcessor, $activeApplicationResolver);

        $this->handleApplicationSwitch();

        $this->handleIgnoreRouteError($ignoreRouteErrorAction);

        $this->configureVite();

        $disableThirdPartyUiAction->execute();

        $baseViewData = [
            'activeApplicationResolver' => $activeApplicationResolver,
            'sharedState' => $incomingShareableLinkPayload,
        ];

        try {
            $routes = $extractRoutesAction->execute(
                routes: RouteFacade::getRoutes()->getRoutes(),
            );

            return view(self::VIEW_NAME, array_merge($baseViewData, [ // @phpstan-ignore-line it cannot find the view.
                'routes' => $routes->toFrontendArray(),
                'headers' => $buildGlobalHeadersAction->execute(),
                'currentUser' => $buildCurrentUserAction->execute(),
            ]));
        } catch (RouteExtractionException $exception) {
            return view(self::VIEW_NAME, array_merge($baseViewData, [  // @phpstan-ignore-line it cannot find the view.
                'routeExtractorException' => $this->formatExtractionException($exception),
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
        $shareParam = request()->get('share');

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
        Vite::useBuildDirectory('/vendor/nimbus');
        Vite::useHotFile(base_path('/vendor/sunchayn/nimbus/resources/dist/hot'));
    }

    private function handleIgnoreRouteError(Actions\IgnoreRouteErrorAction $action): void
    {
        $ignoreData = request()->get('ignore');

        if ($ignoreData === null) {
            return;
        }

        $action->execute(ignoreData: $ignoreData);

        abort(redirect()->to(request()->url()));
    }

    /**
     * @return array<string, mixed>
     */
    private function formatExtractionException(RouteExtractionException $exception): array
    {
        $previous = $exception->getPrevious();

        return [
            'exception' => [
                'message' => $exception->getMessage(),
                'previous' => $previous instanceof \Throwable ? [
                    'message' => $previous->getMessage(),
                    'file' => $previous->getFile(),
                    'line' => $previous->getLine(),
                    'trace' => Str::replace("\n", '<br/>', $previous->getTraceAsString()),
                ] : null,
            ],
            'routeContext' => $exception->getRouteContext(),
            'suggestedSolution' => $exception->getSuggestedSolution(),
            'ignoreData' => $exception->getIgnoreData(),
        ];
    }
}
