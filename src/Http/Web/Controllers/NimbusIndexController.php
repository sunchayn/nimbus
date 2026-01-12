<?php

namespace Sunchayn\Nimbus\Http\Web\Controllers;

use Illuminate\Contracts\Support\Renderable;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Route as RouteFacade;
use Illuminate\Support\Facades\Vite;
use Illuminate\Support\Str;
use Sunchayn\Nimbus\Modules\Config\ActiveApplicationResolver;
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
    ): Renderable|RedirectResponse {
        $disableThirdPartyUiAction->execute();

        if (request()->has('application')) {
            return redirect()
                ->to(request()->fullUrlWithQuery(['application' => null]))
                ->withCookie(cookie()->forever(ActiveApplicationResolver::CURRENT_APPLICATION_COOKIE_NAME, request()->get('application')));
        }

        Vite::useBuildDirectory('/vendor/nimbus');
        Vite::useHotFile(base_path('/vendor/sunchayn/nimbus/resources/dist/hot'));

        if (request()->has('ignore')) {
            $ignoreRouteErrorAction->execute(
                ignoreData: request()->get('ignore'),
            );

            return redirect()->to(request()->url());
        }

        try {
            $routes = $extractRoutesAction
                ->execute(
                    routes: RouteFacade::getRoutes()->getRoutes(),
                );
        } catch (RouteExtractionException $routeExtractionException) {
            return view(self::VIEW_NAME, [ // @phpstan-ignore-line it cannot find the view.
                'routeExtractorException' => $this->renderExtractorException($routeExtractionException),
                'activeApplicationResolver' => $activeApplicationResolver,
            ]);
        }

        return view(self::VIEW_NAME, [ // @phpstan-ignore-line it cannot find the view.
            'routes' => $routes->toFrontendArray(),
            'headers' => $buildGlobalHeadersAction->execute(),
            'currentUser' => $buildCurrentUserAction->execute(),
            'activeApplicationResolver' => $activeApplicationResolver,
        ]);
    }

    /**
     * @return array<string, array<string, array<int|string>|string|null>|string|null>
     */
    private function renderExtractorException(RouteExtractionException $routeExtractionException): array
    {
        return [
            'exception' => [
                'message' => $routeExtractionException->getMessage(),
                'previous' => $routeExtractionException->getPrevious() instanceof \Throwable ? [
                    'message' => $routeExtractionException->getPrevious()->getMessage(),
                    'file' => $routeExtractionException->getPrevious()->getFile(),
                    'line' => $routeExtractionException->getPrevious()->getLine(),
                    'trace' => Str::replace("\n", '<br/>', $routeExtractionException->getPrevious()->getTraceAsString()),
                ] : null,
            ],
            'routeContext' => $routeExtractionException->getRouteContext(),
            'suggestedSolution' => $routeExtractionException->getSuggestedSolution(),
            'ignoreData' => $routeExtractionException->getIgnoreData(),
        ];
    }
}
