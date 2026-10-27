<?php

namespace Workbench\App\Http\Controllers\Api;

use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Workbench\App\Http\Resources\CategoryResource;
use Workbench\App\Models\Category;

class CategoryController
{
    public function index(): AnonymousResourceCollection
    {
        return CategoryResource::collection(Category::orderBy('name')->get());
    }
}
