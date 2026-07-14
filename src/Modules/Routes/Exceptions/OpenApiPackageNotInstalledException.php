<?php

declare(strict_types=1);

namespace Sunchayn\Nimbus\Modules\Routes\Exceptions;

use Illuminate\Support\Str;
use RuntimeException;
use Throwable;

/**
 * Exception thrown when the OpenAPI route extraction strategy is selected
 * but the required `devizzent/cebe-php-openapi` package is not installed.
 */
class OpenApiPackageNotInstalledException extends RuntimeException implements RoutesProcessingException
{
    private const SUGGESTED_SOLUTION = 'Install the package by running: composer require devizzent/cebe-php-openapi';

    public function __construct(
        ?Throwable $previous = null,
    ) {
        parent::__construct(
            message: 'The OpenAPI route extraction strategy requires the "devizzent/cebe-php-openapi" package to be installed.',
            previous: $previous,
        );
    }

    public function getFrontEndIdentifier(): string
    {
        return 'globalException';
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        $previous = $this->getPrevious();

        return [
            'exception' => [
                'message' => $this->getMessage(),
                'previous' => $previous instanceof Throwable ? [
                    'message' => $previous->getMessage(),
                    'file' => $previous->getFile(),
                    'line' => $previous->getLine(),
                    'trace' => Str::replace("\n", '<br/>', $previous->getTraceAsString()),
                ] : null,
            ],
            'suggestedSolution' => self::SUGGESTED_SOLUTION,
        ];
    }
}
