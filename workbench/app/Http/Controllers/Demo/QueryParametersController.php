<?php

namespace Workbench\App\Http\Controllers\Demo;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class QueryParametersController
{
    public function index(Request $request): JsonResponse
    {
        $request->validate(['per_page' => 'integer|min:1|max:100']);

        $filters = $request->only(['status', 'author']);

        return response()->json([
            'search' => $request->query('search'),
            'per_page' => $request->input('per_page', 15),
            'featured' => $request->has('featured'),
            'filters' => $filters,
        ]);
    }
}
