<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Actions\Category\ListCategories;
use App\Http\Resources\CategoryResource;
use App\Models\Baby;
use App\Models\User;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

final readonly class CategoryController
{
    public function index(Baby $baby, ListCategories $action): AnonymousResourceCollection
    {
        /** @var User $user */
        $user = $baby->user;

        return CategoryResource::collection($action->handle($baby, $user));
    }
}
