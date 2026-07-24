<?php

declare(strict_types=1);

namespace Sunchayn\Nimbus\Modules\Extractor\Services\Request;

use Illuminate\Container\Container;
use Illuminate\Contracts\Container\BindingResolutionException;
use Illuminate\Routing\Route;
use Sunchayn\Nimbus\Modules\Extractor\Services\Request\Contracts\RequestSchemaStrategyContract;
use Sunchayn\Nimbus\Modules\Extractor\Services\Request\Strategies\FormRequestStrategy;
use Sunchayn\Nimbus\Modules\Extractor\Services\Request\Strategies\InlineRequestValidatorStrategy;
use Sunchayn\Nimbus\Modules\Extractor\Services\Request\Strategies\SpatieDataObjectStrategy;
use Sunchayn\Nimbus\Modules\Schemas\ValueObjects\Schema;

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
    ) {
        $this->strategies = [
            $container->make(FormRequestStrategy::class),
            $container->make(SpatieDataObjectStrategy::class),
            $container->make(InlineRequestValidatorStrategy::class),
        ];
    }

    public function extract(?Route $route): Schema
    {
        if (! $route instanceof \Illuminate\Routing\Route) {
            return Schema::empty();
        }

        foreach ($this->strategies as $strategy) {
            $schema = $strategy->attempt($route);

            if ($schema !== null) {
                return $schema;
            }
        }

        return Schema::empty();
    }
}
