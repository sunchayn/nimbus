<?php

namespace Workbench\App\Http\Controllers\Demo;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Responses with a content type other than JSON, plus odd status and timing cases.
 */
class ContentController
{
    public function text(): Response
    {
        return response("Plain text response.\nSecond line.", 200, ['Content-Type' => 'text/plain']);
    }

    public function html(): Response
    {
        return response('<h1>Hello</h1><p>An <em>HTML</em> response.</p>', 200, ['Content-Type' => 'text/html']);
    }

    public function xml(): Response
    {
        return response('<?xml version="1.0"?><note><to>Nimbus</to><body>Hello</body></note>', 200, ['Content-Type' => 'application/xml']);
    }

    public function csv(): Response
    {
        return response("id,name\n1,First\n2,Second\n", 200, ['Content-Type' => 'text/csv']);
    }

    public function download(): StreamedResponse
    {
        return response()->streamDownload(fn () => print (random_bytes(256)), 'sample.bin', ['Content-Type' => 'application/octet-stream']);
    }

    public function noContent(): Response
    {
        return response()->noContent();
    }

    public function redirect(): RedirectResponse
    {
        return redirect('/_demo/content/text');
    }

    public function empty(): Response
    {
        return response('', 200);
    }

    public function large(): JsonResponse
    {
        return response()->json(['items' => array_map(
            fn (int $i) => ['id' => $i, 'name' => "Item $i", 'tags' => ['a', 'b']],
            range(1, 2000),
        )]);
    }

    public function slow(Request $request): JsonResponse
    {
        $seconds = min($request->integer('seconds', 3), 10);
        sleep($seconds);

        return response()->json(['waited' => $seconds]);
    }

    public function invalidJson(): Response
    {
        return response('{"broken": ', 200, ['Content-Type' => 'application/json']);
    }

    public function status(int $code): Response
    {
        abort_unless($code >= 100 && $code <= 599, 400, 'The code must be between 100 and 599.');

        return response(json_encode(['status' => $code]), $code, ['Content-Type' => 'application/json']);
    }
}
