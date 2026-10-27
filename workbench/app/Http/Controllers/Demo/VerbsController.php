<?php

namespace Workbench\App\Http\Controllers\Demo;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class VerbsController
{
    public function post(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users',
            'age' => 'required|integer|min:18|max:120',
        ]);

        return response()->json([
            'message' => 'Validation passed',
            'data' => $validated,
        ]);
    }

    public function patch(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => 'sometimes|string|max:255',
            'email' => 'sometimes|email|unique:users',
            'age' => 'sometimes|integer|min:18|max:120',
        ]);

        return response()->json([
            'message' => 'Validation passed',
            'data' => $validated,
        ]);
    }

    public function get(): JsonResponse
    {
        return response()->json([
            'message' => 'Hey!',
        ]);
    }
}
