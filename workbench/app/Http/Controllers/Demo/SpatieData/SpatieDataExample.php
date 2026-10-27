<?php

namespace Workbench\App\Http\Controllers\Demo\SpatieData;

use Spatie\LaravelData\Attributes\Validation\Max;
use Spatie\LaravelData\Data;
use Spatie\LaravelData\Support\Validation\ValidationContext;

class SpatieDataExample extends Data
{
    public function __construct(
        public string $title,
        #[Max(255)]
        public ?string $artist,
    ) {}

    public static function rules(?ValidationContext $context = null): array
    {
        return [
            'extra_email' => 'sometimes|email',
        ];
    }
}
