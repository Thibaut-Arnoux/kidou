<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Actions\Baby\CreateBaby;
use App\Actions\Baby\DeleteBaby;
use App\Actions\Baby\ListBabies;
use App\Actions\Baby\UpdateBaby;
use App\Http\Requests\Api\V1\StoreBabyRequest;
use App\Http\Requests\Api\V1\UpdateBabyRequest;
use App\Http\Resources\BabyResource;
use App\Models\Baby;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Symfony\Component\HttpFoundation\Response;

final readonly class BabyController
{
    public function index(ListBabies $action): AnonymousResourceCollection
    {
        return BabyResource::collection($action->handle());
    }

    public function store(StoreBabyRequest $request, CreateBaby $action): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();

        /** @var string $nickname */
        $nickname = $request->validated('nickname');

        $baby = $action->handle($user, $nickname);

        return BabyResource::make($baby)
            ->response()
            ->setStatusCode(Response::HTTP_CREATED);
    }

    public function show(Baby $baby): BabyResource
    {
        return BabyResource::make($baby);
    }

    public function update(UpdateBabyRequest $request, Baby $baby, UpdateBaby $action): BabyResource
    {
        /** @var string $nickname */
        $nickname = $request->validated('nickname');

        return BabyResource::make($action->handle($baby, $nickname));
    }

    public function destroy(Baby $baby, DeleteBaby $action): Response
    {
        $action->handle($baby);

        return response()->noContent();
    }
}
