<?php

namespace Workbench\App\Http\Controllers\Demo;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\JsonResource;
use Workbench\App\Http\Controllers\Demo\SpatieData\SpatieDataExample;

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
}
