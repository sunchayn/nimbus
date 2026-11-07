<?php

namespace Workbench\App\Http\Controllers\Demo;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

/**
 * Returns what the server received, to check headers, cookies, variables and bodies.
 */
class EchoController
{
    public function echo(Request $request): JsonResponse
    {
        return response()->json([
            'method' => $request->method(),
            'path' => $request->path(),
            'query' => $request->query(),
            'headers' => collect($request->headers->all())->map(fn ($v) => $v[0])->all(),
            'cookies' => $request->cookies->all(),
            'content_type' => $request->header('Content-Type'),
            'body' => $request->all(),
            'raw' => $request->getContent(),
            'files' => collect($request->allFiles())->map(fn ($f) => $f->getClientOriginalName())->all(),
        ]);
    }

    public function form(Request $request): JsonResponse
    {
        return response()->json($request->validate([
            'username' => 'required|string|min:3',
            'remember' => 'sometimes|boolean',
        ]));
    }

    public function text(Request $request): JsonResponse
    {
        return response()->json(['received' => $request->getContent(), 'length' => strlen($request->getContent())]);
    }

    public function redirect(): RedirectResponse
    {
        return redirect('/demo/echo');
    }

    public function slow(): JsonResponse
    {
        sleep(3);

        return response()->json(['slept' => 3]);
    }
}
