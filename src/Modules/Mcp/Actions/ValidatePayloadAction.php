<?php

declare(strict_types=1);

namespace Sunchayn\Nimbus\Modules\Mcp\Actions;

use Sunchayn\Nimbus\Modules\Mcp\Exceptions\McpToolException;
use Sunchayn\Nimbus\Modules\Schemas\Services\SchemaPayloadValidator;

/** @final */
class ValidatePayloadAction
{
    public function __construct(
        private readonly ExtractRoutesByApplicationAction $extractRoutesByApplicationAction,
        private readonly SchemaPayloadValidator $schemaPayloadValidator,
    ) {}

    /**
     * @param  array<array-key, mixed>  $body
     * @return array{route: string, valid: bool, errors: list<array{path: string, rule: string, message: string}>}
     *
     * @throws McpToolException
     */
    public function execute(string $route, array $body, ?string $application = null): array
    {
        $extractedRoute = $this->extractRoutesByApplicationAction->execute(application: $application)->findRouteOrFail($route)->route;

        $errors = $this->schemaPayloadValidator->validate(
            $extractedRoute->schema->toJsonSchema(),
            $body,
        );

        return [
            'route' => $extractedRoute->id(),
            'valid' => $errors === [],
            'errors' => $errors,
        ];
    }
}
