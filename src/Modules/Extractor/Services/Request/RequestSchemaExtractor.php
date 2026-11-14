<?php

declare(strict_types=1);

namespace Sunchayn\Nimbus\Modules\Extractor\Services\Request;

use Illuminate\Container\Container;
use Illuminate\Contracts\Container\BindingResolutionException;
use Illuminate\Routing\Route;
use Sunchayn\Nimbus\Modules\Extractor\Actions\ReadRequestQueryKeysAction;
use Sunchayn\Nimbus\Modules\Extractor\Services\Request\Contracts\RequestSchemaStrategyContract;
use Sunchayn\Nimbus\Modules\Extractor\Services\Request\Strategies\FormRequestStrategy;
use Sunchayn\Nimbus\Modules\Extractor\Services\Request\Strategies\InlineRequestValidatorStrategy;
use Sunchayn\Nimbus\Modules\Extractor\Services\Request\Strategies\SpatieDataObjectStrategy;
use Sunchayn\Nimbus\Modules\Schemas\Contracts\SchemaPropertyInterface;
use Sunchayn\Nimbus\Modules\Schemas\ValueObjects\Schema;
use Sunchayn\Nimbus\Modules\Schemas\ValueObjects\StringSchemaProperty;

/**
 * Extracts a request validation Schema for a given route by trying strategies in priority order.
 */
class RequestSchemaExtractor
{
    /** @var RequestSchemaStrategyContract[] */
    protected array $strategies = [];

    /**
     * @throws BindingResolutionException
     */
    public function __construct(
        Container $container,
        private readonly ReadRequestQueryKeysAction $readRequestQueryKeysAction,
    ) {
        $this->strategies = [
            $container->make(FormRequestStrategy::class),
            $container->make(SpatieDataObjectStrategy::class),
            $container->make(InlineRequestValidatorStrategy::class),
        ];
    }

    public function extract(?Route $route): Schema
    {
        if (! $route instanceof Route) {
            return Schema::empty();
        }

        foreach ($this->strategies as $strategy) {
            $schema = $strategy->attempt($route);

            if ($schema !== null) {
                return $this->withQueryParameters($route, $schema);
            }
        }

        return $this->withQueryParameters($route, Schema::empty());
    }

    /**
     * GET routes have no body, so the keys the controller reads become optional string properties.
     * A key that already has rules keeps its rule based type.
     */
    private function withQueryParameters(Route $route, Schema $schema): Schema
    {
        if (! in_array('GET', $route->methods(), true)) {
            return $schema;
        }

        $controllerClass = $route->getControllerClass();
        $method = $route->getActionMethod();

        if (empty($controllerClass) || empty($method)) {
            return $schema;
        }

        /** @var class-string $controllerClass */
        $known = array_map(fn (SchemaPropertyInterface $property): string => $property->getName(), $schema->properties);

        $extra = array_map(
            fn (string $key): StringSchemaProperty => new StringSchemaProperty(name: $key),
            array_values(array_diff($this->readRequestQueryKeysAction->execute($controllerClass, $method), $known)),
        );

        return $extra === [] ? $schema : new Schema([...$schema->properties, ...$extra], $schema->extractionError);
    }
}
