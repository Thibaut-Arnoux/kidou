<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Actions\Category\ListCategories;
use App\Http\Resources\CategoryResource;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

final readonly class CategoryController
{
    public function index(ListCategories $action): AnonymousResourceCollection
    {
        return CategoryResource::collection($action->handle());
    }
}
