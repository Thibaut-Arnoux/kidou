<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Actions\Baby\CreateBaby;
use App\Http\Requests\Api\V1\StoreBabyRequest;
use App\Http\Resources\BabyResource;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Symfony\Component\HttpFoundation\Response;

final readonly class StoreBabyController
{
    public function __invoke(StoreBabyRequest $request, CreateBaby $action): JsonResponse
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
}
