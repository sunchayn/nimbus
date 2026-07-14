<?php

namespace Sunchayn\Nimbus\Modules\Relay\Responses;

use Illuminate\Http\Client\Response;
use Illuminate\Support\Str;
use Sunchayn\Nimbus\Modules\Relay\Parsers\VarDumpParser\VarDumpParser;

class DumpAndDieResponse extends Response
{
    public const DUMP_AND_DIE_STATUS_CODE = 999;

    public function __construct($response)
    {
        parent::__construct($response);

        $this->preDecodeBody((string) $this->response->getBody());
    }

    public function getStatusCode(): int
    {
        return self::DUMP_AND_DIE_STATUS_CODE;
    }

    public function status(): int
    {
        return self::DUMP_AND_DIE_STATUS_CODE;
    }

    private function preDecodeBody(string $body): void
    {
        // Normally, we would overwrite the `->json` method. But there was a breaking change to the method in L12.48.
        // If we overwrite the new `->json` and conform its new signature, we lose support for earlier versions.
        // Instead, we need to pre-decode the body so that when calling ->json() it will use the below array.

        [
            'source' => $source,
            'dumps' => $dumps,
        ] = resolve(VarDumpParser::class)->parse($body)->toArray();

        $this->decoded = [
            'id' => Str::uuid()->toString(),
            'timestamp' => now()->format('Y-m-d H:i:s'),
            'source' => $source,
            'dumps' => $dumps,
        ];

        // New property as part of v13.14.0. reminder, we are pretending to cache a decoded version here.
        // @phpstan-ignore-next-line
        if (property_exists($this, 'decodedJson')) {
            $this->decodedJson = true;
            $this->decodingFlags = self::$defaultJsonDecodingFlags;
        }
    }
}
