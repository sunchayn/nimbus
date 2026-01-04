<?php

namespace Sunchayn\Nimbus\Modules\Relay\Responses;

use Illuminate\Http\Client\Response;
use Illuminate\Support\Str;
use Sunchayn\Nimbus\Modules\Relay\Parsers\VarDumpParser\VarDumpParser;

class DieAndDumpResponse extends Response
{
    public const DIE_AND_DUMP_STATUS_CODE = 999;

    public function getStatusCode(): int
    {
        return self::DIE_AND_DUMP_STATUS_CODE;
    }

    /**
     * @return array<string, string|array<array-key, mixed>>
     */
    public function json($key = null, $default = null): array
    {
        [
            'source' => $source,
            'dumps' => $dumps,
        ] = resolve(VarDumpParser::class)->parse($this->response->getBody())->toArray();

        return [
            'id' => Str::uuid()->toString(),
            'timestamp' => now()->format('Y-m-d H:i:s'),
            'source' => $source,
            'dumps' => $dumps,
        ];
    }
}
