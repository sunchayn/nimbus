<?php

namespace Sunchayn\Nimbus\Modules\Routes\Exceptions;

use Illuminate\Support\Str;
use RuntimeException;
use Throwable;

/**
 * Exception thrown when an OpenAPI specification file cannot be parsed or contains errors.
 */
class OpenApiParsingException extends RuntimeException implements RoutesProcessingException
{
    private readonly string $suggestedSolution;

    public function __construct(
        string $filePath,
        string $parsingError,
        ?Throwable $previous = null,
    ) {
        $this->suggestedSolution = 'Verify that the file exists and contains a valid OpenAPI 3.x specification. Error: '.$parsingError;

        parent::__construct(
            message: 'Failed to parse OpenAPI specification file: '.$filePath,
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
            'suggestedSolution' => $this->suggestedSolution,
        ];
    }
}
