<?php

declare(strict_types=1);

namespace Sunchayn\Nimbus\Modules\Magic\Actions;

use Illuminate\Support\Str;
use Sunchayn\Nimbus\Modules\Magic\DataTransferObjects\InsightData;
use Sunchayn\Nimbus\Modules\Magic\Services\Insights\InsightStore;

/** @final */
class PullInsightAction
{
    public const HEADER = InsightStore::HEADER;

    public function __construct(private readonly InsightStore $insightStore) {}

    public static function newId(): string
    {
        return (string) Str::uuid();
    }

    /**
     * Reads the insight file the target request wrote, and deletes it. Returns null when there is none.
     */
    public function execute(string $id): ?InsightData
    {
        $data = $this->insightStore->pull($id);

        return $data === null ? null : InsightData::fromArray($data);
    }
}
