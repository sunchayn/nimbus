<?php

namespace Workbench\App\Http\Controllers\Demo;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\Resources\Json\JsonResource;
use Workbench\App\Http\Controllers\Demo\SpatieData\SpatieDataExample;
use Workbench\App\Http\Resources\DemoPostResource;
use Workbench\App\Models\Product;

class DemoUserResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'id' => 101,
            'name' => 'Jane Doe',
            'email' => 'jane@example.com',
            'is_active' => true,
        ];
    }
}

class ResponseDemoController
{
    public function jsonResource(): DemoUserResource
    {
        return new DemoUserResource(null);
    }

    public function nestedJson(): JsonResponse
    {
        return response()->json([
            'id' => 1001,
            'user' => [
                'name' => 'Alex Developer',
                'role' => 'admin',
                'preferences' => [
                    'theme' => 'dark',
                    'notifications' => true,
                ],
            ],
            'tags' => ['api', 'nimbus', 'testing'],
        ]);
    }

    public function spatieData(): SpatieDataExample
    {
        return new SpatieDataExample(
            title: 'Sample Song',
            artist: 'Awesome Artist'
        );
    }

    public function inlineJson(): JsonResponse
    {
        return response()->json([
            'status' => 'success',
            'code' => 200,
            'data' => [
                'item_id' => 'item_99',
                'title' => 'Sample Item',
            ],
        ]);
    }

    public function rawPayload(): array
    {
        return [
            'message' => 'raw payload array',
            'active' => true,
            'count' => 42,
        ];
    }

    public function paginated(): AnonymousResourceCollection
    {
        return DemoPostResource::collection(Product::query()->paginate(15));
    }

    public function simplePaginated(): AnonymousResourceCollection
    {
        return DemoPostResource::collection(Product::query()->simplePaginate(15));
    }

    public function cursorPaginated(): AnonymousResourceCollection
    {
        $products = Product::query()->orderBy('id')->cursorPaginate(15);

        return DemoPostResource::collection($products);
    }

    public function resourceWithOptionalKeys(): DemoPostResource
    {
        return new DemoPostResource(null);
    }
}
