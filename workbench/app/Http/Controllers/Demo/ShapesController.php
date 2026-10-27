<?php

namespace Workbench\App\Http\Controllers\Demo;

use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Cookie;
use Illuminate\Support\Str;
use Workbench\App\Http\Requests\Demo\ArrayOfPrimitivesRequest;
use Workbench\App\Http\Requests\Demo\NestedObjectRequest;

/**
 * Request shapes that need a FormRequest, and cookies set by the response.
 */
class ShapesController
{
    public function nestedObject(NestedObjectRequest $request): JsonResponse
    {
        Cookie::queue('::example::', Str::uuid());
        Cookie::queue('appearance', 'light');

        return response()->json([
            'message' => 'Order created successfully',
            'data' => [
                'id' => 123,
                'user_id' => $request->validated()['user_id'],
                'status' => 'pending',
                'total' => 299.98,
                'items_count' => count($request->validated()['items']),
                'created_at' => now()->toISOString(),
            ],
        ], 201);
    }

    public function arrayOfPrimitives(ArrayOfPrimitivesRequest $request): JsonResponse
    {
        Cookie::queue('::example::', Str::uuid());
        Cookie::queue('appearance', 'light');

        return response()->json([
            'message' => 'Product created successfully',
            'data' => [
                'id' => 123,
                'name' => $request->validated()['name'],
                'description' => $request->validated()['description'],
                'price' => $request->validated()['price'],
                'category_id' => $request->validated()['category_id'],
                'status' => 'active',
                'created_at' => now()->toISOString(),
            ],
        ], 201);
    }

    public function unresolvableRules(\Workbench\App\Http\Requests\Demo\UnresolvableRulesRequest $request): JsonResponse
    {
        return response()->json(['message' => 'Never reached.']);
    }
}
