<?php

namespace Sunchayn\Nimbus\Modules\Routes\Actions;

use cebe\openapi\spec\MediaType;
use cebe\openapi\spec\OpenApi;
use cebe\openapi\spec\Operation;
use cebe\openapi\spec\PathItem;
use cebe\openapi\spec\RequestBody;
use cebe\openapi\spec\Schema as OpenApiSchema;
use Illuminate\Support\Arr;
use Sunchayn\Nimbus\Modules\Config\ActiveApplicationResolver;
use Sunchayn\Nimbus\Modules\Routes\DataTransferObjects\ExtractedRoute;
use Sunchayn\Nimbus\Modules\Routes\ValueObjects\Endpoint;
use Sunchayn\Nimbus\Modules\Schemas\Contracts\SchemaPropertyInterface;
use Sunchayn\Nimbus\Modules\Schemas\Enums\StringFormat;
use Sunchayn\Nimbus\Modules\Schemas\ValueObjects\ArraySchemaProperty;
use Sunchayn\Nimbus\Modules\Schemas\ValueObjects\BooleanSchemaProperty;
use Sunchayn\Nimbus\Modules\Schemas\ValueObjects\IntegerSchemaProperty;
use Sunchayn\Nimbus\Modules\Schemas\ValueObjects\NumberSchemaProperty;
use Sunchayn\Nimbus\Modules\Schemas\ValueObjects\ObjectSchemaProperty;
use Sunchayn\Nimbus\Modules\Schemas\ValueObjects\Schema;
use Sunchayn\Nimbus\Modules\Schemas\ValueObjects\StringSchemaProperty;

/**
 * Parses an OpenAPI specification and extracts routes with their schemas.
 */
class ExtractOpenApiRoutesAction
{
    private const HTTP_METHODS = ['get', 'post', 'put', 'patch', 'delete', 'options'];

    public function __construct(
        private readonly ActiveApplicationResolver $activeApplicationResolver,
    ) {}

    /**
     * Parse an OpenAPI specification and extract routes.
     *
     * @return ExtractedRoute[]
     */
    public function execute(
        OpenApi $openapi,
        string $version,
    ): array {
        if (empty($openapi->paths)) {
            return [];
        }

        return Arr::flatten(
            Arr::map(
                iterator_to_array($openapi->paths),
                fn (PathItem $pathItem, string $path): array => $this->extractRoutesFromPathItem($path, $pathItem, $version),
            ),
        );
    }

    /**
     * @return ExtractedRoute[]
     */
    private function extractRoutesFromPathItem(
        string $path,
        PathItem $pathItem,
        string $version,
    ): array {
        $prefix = $this->activeApplicationResolver->getRoutesPrefix();
        $path = ltrim($path, '/');

        if (! str_starts_with($path, $prefix)) {
            $path = sprintf('%s/%s', $prefix, $path);
        }

        $uri = $this->normalizeUri($path, $version);

        return collect(self::HTTP_METHODS)
            ->filter(fn (string $method): bool => $pathItem->$method instanceof Operation)
            ->map(
                function ($method) use ($uri, $pathItem): ExtractedRoute {
                    $operation = $pathItem->$method;

                    return new ExtractedRoute(
                        uri: Endpoint::fromRaw(
                            uri: $uri,
                            routesPrefix: $this->activeApplicationResolver->getRoutesPrefix(),
                            isVersioned: $this->activeApplicationResolver->isVersioned(),
                        ),
                        methods: [strtoupper($method)],
                        schema: $this->extractSchemaFromOperation($operation),
                        metadata: [
                            'operationId' => $operation->operationId !== null
                                ? trim($operation->operationId, '/')
                                : null,
                        ],
                    );
                },
            )
            ->all();
    }

    private function extractSchemaFromOperation(Operation $operation): Schema
    {
        $requestBody = $operation->requestBody;

        if (! $requestBody instanceof RequestBody) {
            return Schema::empty();
        }

        $jsonContent = $requestBody->content['application/json'] ?? null;

        if (! $jsonContent instanceof MediaType) {
            return Schema::empty();
        }

        $openApiSchema = $jsonContent->schema;

        if (! $openApiSchema instanceof OpenApiSchema) {
            return Schema::empty();
        }

        return $this->convertOpenApiSchemaToInternalSchema($openApiSchema);
    }

    private function convertOpenApiSchemaToInternalSchema(OpenApiSchema $openApiSchema): Schema
    {
        if ($openApiSchema->type !== 'object') {
            return Schema::empty();
        }

        $properties = [];

        $requiredFields = $openApiSchema->required ?? [];

        foreach ($openApiSchema->properties ?? [] as $propertyName => $propertySchema) {
            if (! $propertySchema instanceof OpenApiSchema) {
                continue;
            }

            $isRequired = in_array($propertyName, $requiredFields, true);

            $properties[] = $this->convertPropertySchema($propertyName, $propertySchema, $isRequired);
        }

        return new Schema(properties: $properties);
    }

    private function convertPropertySchema(
        string $propertyName,
        OpenApiSchema $openApiSchema,
        bool $isRequired,
    ): SchemaPropertyInterface {
        return match ($openApiSchema->type) {
            'string' => $this->createStringProperty($propertyName, $openApiSchema, $isRequired),
            'integer' => $this->createIntegerProperty($propertyName, $openApiSchema, $isRequired),
            'number' => $this->createNumberProperty($propertyName, $openApiSchema, $isRequired),
            'boolean' => new BooleanSchemaProperty(
                name: $propertyName,
                required: $isRequired,
            ),
            'array' => $this->convertArrayProperty($propertyName, $openApiSchema, $isRequired),
            'object' => $this->convertObjectProperty($propertyName, $openApiSchema, $isRequired),
            default => new StringSchemaProperty(
                name: $propertyName,
                required: $isRequired,
            ),
        };
    }

    private function createStringProperty(
        string $propertyName,
        OpenApiSchema $openApiSchema,
        bool $isRequired,
    ): StringSchemaProperty {
        return new StringSchemaProperty(
            name: $propertyName,
            required: $isRequired,
            stringFormat: $openApiSchema->format
                ? StringFormat::tryFrom($openApiSchema->format)
                : null,
            enum: $openApiSchema->enum,
            minLength: $openApiSchema->minLength,
            maxLength: $openApiSchema->maxLength,
            pattern: $openApiSchema->pattern,
        );
    }

    private function createIntegerProperty(
        string $propertyName,
        OpenApiSchema $openApiSchema,
        bool $isRequired,
    ): IntegerSchemaProperty {
        return new IntegerSchemaProperty(
            name: $propertyName,
            required: $isRequired,
            minimum: $openApiSchema->minimum,
            maximum: $openApiSchema->maximum,
            enum: $openApiSchema->enum,
        );
    }

    private function createNumberProperty(
        string $propertyName,
        OpenApiSchema $openApiSchema,
        bool $isRequired,
    ): NumberSchemaProperty {
        return new NumberSchemaProperty(
            name: $propertyName,
            required: $isRequired,
            minimum: $openApiSchema->minimum,
            maximum: $openApiSchema->maximum,
        );
    }

    private function convertArrayProperty(
        string $propertyName,
        OpenApiSchema $openApiSchema,
        bool $isRequired,
    ): ArraySchemaProperty {
        $itemsSchema = $openApiSchema->items;

        $schemaProperty = null;

        if ($itemsSchema instanceof OpenApiSchema) {
            $schemaProperty = $this->convertPropertySchema(
                propertyName: 'items',
                openApiSchema: $itemsSchema,
                isRequired: false,
            );
        }

        return new ArraySchemaProperty(
            name: $propertyName,
            required: $isRequired,
            schemaProperty: $schemaProperty,
            minItems: $openApiSchema->minItems,
            maxItems: $openApiSchema->maxItems,
        );
    }

    private function convertObjectProperty(
        string $propertyName,
        OpenApiSchema $openApiSchema,
        bool $isRequired,
    ): ObjectSchemaProperty {
        return new ObjectSchemaProperty(
            name: $propertyName,
            required: $isRequired,
            schema: $this->convertOpenApiSchemaToInternalSchema($openApiSchema),
        );
    }

    private function normalizeUri(string $path, string $version): string
    {
        $prefix = $this->activeApplicationResolver->getRoutesPrefix();

        // Remove prefix to handle base path consistently
        $cleanedPath = ltrim(substr($path, strlen($prefix)), '/');

        if ($this->activeApplicationResolver->isVersioned()) {
            return sprintf('%s/%s/%s', $prefix, $version, $cleanedPath);
        }

        return sprintf('%s/%s', $prefix, $cleanedPath);
    }
}
