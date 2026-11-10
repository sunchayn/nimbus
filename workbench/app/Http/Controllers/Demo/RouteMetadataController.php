<?php

namespace Workbench\App\Http\Controllers\Demo;

use Illuminate\Http\JsonResponse;
use Workbench\App\Models\Product;

class RouteMetadataController
{
    /**
     * Show a product that needs a signed in user.
     */
    public function show(Product $product): JsonResponse
    {
        return response()->json($product);
    }
}
