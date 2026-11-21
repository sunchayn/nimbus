<?php

declare(strict_types=1);

namespace Sunchayn\Nimbus\Modules\Mcp\Actions;

use Sunchayn\Nimbus\Modules\Magic\Actions\ReadLookupValuesAction;
use Sunchayn\Nimbus\Modules\Mcp\Exceptions\McpToolException;
use Sunchayn\Nimbus\Modules\Schemas\Services\SchemaPayloadBuilder;

/** @final */
class BuildPayloadAction
{
    public const MODES = ['valid', 'minimal', 'invalid'];

    public function __construct(
        private readonly ExtractRoutesByApplicationAction $extractRoutesByApplicationAction,
        private readonly SchemaPayloadBuilder $schemaPayloadBuilder,
        private readonly ReadLookupValuesAction $readLookupValuesAction,
    ) {}

    /**
     * @param  array<string, mixed>  $overrides  Values to set, where a dotted key such as `user.name` reaches a nested field.
     * @return array{route: string, mode: string, payload: array<string, mixed>, notes: list<string>}
     *
     * @throws McpToolException
     */
    public function execute(
        string $route,
        string $mode = 'valid',
        array $overrides = [],
        ?string $application = null,
    ): array {
        $this->assertKnownMode(mode: $mode);

        $extractedRoute = $this
            ->extractRoutesByApplicationAction
            ->execute(application: $application)
            ->findRouteOrFail($route)
            ->route;

        $lookups = $extractedRoute->schema->listLookups();

        $lookupValues = $this->readLookupValuesAction->execute(
            allowed: $lookups,
            requested: $lookups,
        );

        $builtPayload = $this->schemaPayloadBuilder->build(
            schema: $extractedRoute->schema->toJsonSchema(),
            mode: $mode,
            overrides: $overrides,
            lookupValues: $lookupValues,
        );

        return [
            'route' => $extractedRoute->id(),
            'mode' => $mode,
            ...$builtPayload,
        ];
    }

    /**
     * @throws McpToolException
     */
    private function assertKnownMode(string $mode): void
    {
        if (in_array($mode, self::MODES, true)) {
            return;
        }

        $knownModes = implode(', ', self::MODES);

        throw McpToolException::becauseInputIsInvalid(
            message: "Unknown mode `{$mode}`.",
            hint: "Use one of {$knownModes}.",
        );
    }
}
