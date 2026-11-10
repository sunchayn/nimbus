<?php

declare(strict_types=1);

namespace Sunchayn\Nimbus\Modules\Magic\Toolbox\Actions;

use Illuminate\Routing\Exceptions\UrlGenerationException;
use Illuminate\Routing\Router;
use Illuminate\Routing\UrlGenerator;
use Illuminate\Support\Facades\Date;
use Sunchayn\Nimbus\Modules\Magic\Toolbox\DataTransferObjects\CreateSignedUrlData;
use Sunchayn\Nimbus\Modules\Magic\Toolbox\Exceptions\InvalidToolInputException;

/** @final */
class CreateSignedUrlAction
{
    public function __construct(
        private readonly UrlGenerator $urlGenerator,
        private readonly Router $router,
    ) {}

    /**
     * @throws InvalidToolInputException
     */
    public function execute(CreateSignedUrlData $data): string
    {
        if (! $this->router->has($data->route)) {
            throw InvalidToolInputException::becauseRouteIsUnknown($data->route);
        }

        try {
            return $data->expiresInMinutes === null
                ? $this->urlGenerator->signedRoute($data->route, $data->parameters)
                : $this->urlGenerator->temporarySignedRoute($data->route, Date::now()->addMinutes($data->expiresInMinutes), $data->parameters);
        } catch (UrlGenerationException $urlGenerationException) {
            throw InvalidToolInputException::becauseUrlCannotBeGenerated($urlGenerationException->getMessage());
        }
    }
}
