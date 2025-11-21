<?php

namespace Sunchayn\Nimbus\Modules\Routes\Extractor;

use Illuminate\Container\Container;
use Sunchayn\Nimbus\Modules\Routes\Extractor\Strategies\ExtractorStrategyContract;
use Sunchayn\Nimbus\Modules\Routes\Extractor\Strategies\FormRequestExtractorStrategy;
use Sunchayn\Nimbus\Modules\Routes\Extractor\Strategies\InlineRequestValidatorExtractorStrategy;
use Sunchayn\Nimbus\Modules\Routes\Extractor\Strategies\SpatieDataObjectExtractorStrategy;
use Sunchayn\Nimbus\Modules\Routes\ValueObjects\ExtractableRoute;
use Sunchayn\Nimbus\Modules\Schemas\ValueObjects\Schema;

class SchemaExtractor
{
    /** @var ExtractorStrategyContract[] */
    protected array $strategies = [];

    public function __construct(
        Container $container,
    ) {
        $this->strategies = [
            // Define in the extraction stragies in their execution order.
            // Only one strategy will run, and that will be the first matching strategy.
            $container->make(FormRequestExtractorStrategy::class),
            $container->make(SpatieDataObjectExtractorStrategy::class),
            $container->make(InlineRequestValidatorExtractorStrategy::class), // <- Must be the last one, so previous ones can match.
        ];
    }

    public function extract(ExtractableRoute $extractableRoute): Schema
    {
        foreach ($this->strategies as $strategy) {
            if ($strategy->matches($extractableRoute)) {
                /** @var ExtractorStrategyContract $strategy */
                return $strategy->extract($extractableRoute);
            }
        }

        return Schema::empty();
    }
}
