<?php

namespace Workbench\App\Http\Controllers\Api;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Routes that are intentionally missing from openapi.yaml.
 */
class MiscController
{
    public function me(Request $request): JsonResponse
    {
        return response()->json($request->user()->only('id', 'name', 'email', 'role', 'status'));
    }

    public function health(): JsonResponse
    {
        return response()->json(['status' => 'ok', 'time' => now()->toISOString()]);
    }

    public function salesReport(Request $request): JsonResponse
    {
        $request->validate(['from' => 'required|date', 'to' => 'required|date|after_or_equal:from']);

        return response()->json(['total' => 0, 'orders' => 0]);
    }
}
