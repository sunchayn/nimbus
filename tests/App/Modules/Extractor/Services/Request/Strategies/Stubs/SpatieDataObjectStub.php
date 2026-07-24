<?php

declare(strict_types=1);

namespace Sunchayn\Nimbus\Tests\App\Modules\Extractor\Services\Request\Strategies\Stubs;

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
