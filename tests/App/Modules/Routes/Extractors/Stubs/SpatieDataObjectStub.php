<?php

namespace Sunchayn\Nimbus\Tests\App\Modules\Routes\Extractors\Stubs;

use Spatie\LaravelData\Attributes\Validation\Max;
use Spatie\LaravelData\Data;

class SpatieDataObjectStub extends Data
{
    public function __construct(
        public string $title,
        #[Max(20)]
        public ?string $artist,
    ) {}
}
