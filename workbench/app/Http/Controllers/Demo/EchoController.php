<?php

namespace Workbench\App\Http\Controllers\Demo;

use Illuminate\Http\JsonResponse;
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

    public function upload(Request $request): JsonResponse
    {
        $data = $request->validate([
            'title' => 'required|string|max:100',
            'file' => 'required|file|max:2048',
            'attachments' => 'sometimes|array|max:3',
            'attachments.*' => 'file|max:2048',
        ]);

        return response()->json([
            'title' => $data['title'],
            'file' => $data['file']->getClientOriginalName(),
            'size' => $data['file']->getSize(),
        ], 201);
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
}
