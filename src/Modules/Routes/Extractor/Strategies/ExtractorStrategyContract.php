<?php

declare(strict_types=1);

namespace Sunchayn\Nimbus\Modules\Routes\Extractor\Strategies;

use Sunchayn\Nimbus\Modules\Routes\ValueObjects\ExtractableRoute;
use Sunchayn\Nimbus\Modules\Schemas\ValueObjects\Schema;

interface ExtractorStrategyContract
{
    public function matches(ExtractableRoute $extractableRoute): bool;

    public function extract(ExtractableRoute $extractableRoute): Schema;
}
