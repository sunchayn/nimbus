<?php

namespace Workbench\App\Http\Controllers\Api;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;
use Workbench\App\Http\Requests\Api\StoreProductRequest;
use Workbench\App\Http\Requests\Api\UpdateProductRequest;
use Workbench\App\Http\Resources\ProductResource;
use Workbench\App\Models\Product;

class ProductController
{
    public function index(Request $request): AnonymousResourceCollection
    {
        $request->validate([
            'category_id' => 'sometimes|integer',
            'status' => 'sometimes|in:active,inactive,discontinued',
            'search' => 'sometimes|string|max:50',
            'per_page' => 'sometimes|integer|min:1|max:100',
        ]);

        $products = Product::with('category')
            ->when($request->query('category_id'), fn ($q, $id) => $q->where('category_id', $id))
            ->when($request->query('status'), fn ($q, $status) => $q->where('status', $status))
            ->when($request->query('search'), fn ($q, $term) => $q->where('name', 'like', "%$term%"))
            ->paginate($request->integer('per_page', 10));

        return ProductResource::collection($products);
    }

    public function show(Product $product): ProductResource
    {
        return new ProductResource($product->load('category'));
    }

    public function store(StoreProductRequest $request): JsonResponse
    {
        $product = Product::create($request->validated());

        return (new ProductResource($product->load('category')))->response()->setStatusCode(201);
    }

    public function update(UpdateProductRequest $request, Product $product): ProductResource
    {
        $product->update($request->validated());

        return new ProductResource($product->load('category'));
    }

    public function restock(Request $request, Product $product): ProductResource
    {
        $data = $request->validate(['quantity' => 'required|integer|min:1|max:1000']);
        $product->increment('stock_quantity', $data['quantity']);

        return new ProductResource($product->refresh());
    }

    public function destroy(Product $product): Response|JsonResponse
    {
        if (DB::table('order_items')->where('product_id', $product->id)->exists()) {
            return response()->json(['message' => 'The product is used by an order.'], 409);
        }

        $product->delete();

        return response()->noContent();
    }
}
