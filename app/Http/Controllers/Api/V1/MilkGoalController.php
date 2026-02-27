<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Actions\MilkGoal\CreateMilkGoal;
use App\Actions\MilkGoal\DeleteMilkGoal;
use App\Actions\MilkGoal\ListMilkGoals;
use App\Actions\MilkGoal\ShowMilkGoal;
use App\Actions\MilkGoal\UpdateMilkGoal;
use App\Http\Requests\Api\V1\StoreMilkGoalRequest;
use App\Http\Requests\Api\V1\UpdateMilkGoalRequest;
use App\Http\Resources\MilkGoalResource;
use App\Models\Baby;
use App\Models\MilkGoal;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Symfony\Component\HttpFoundation\Response;

final readonly class MilkGoalController
{
    public function index(Baby $baby, ListMilkGoals $action): AnonymousResourceCollection
    {
        return MilkGoalResource::collection($action->handle($baby));
    }

    public function store(StoreMilkGoalRequest $request, Baby $baby, CreateMilkGoal $action): JsonResponse
    {
        /** @var string $date */
        $date = $request->validated('date');

        /** @var int $goal */
        $goal = $request->validated('goal');

        /** @var ?string $uuid */
        $uuid = $request->validated('uuid');

        $milkGoal = $action->handle($baby, $date, $goal, $uuid);

        return MilkGoalResource::make($milkGoal)
            ->response()
            ->setStatusCode(Response::HTTP_CREATED);
    }

    public function show(MilkGoal $milkGoal, ShowMilkGoal $action): MilkGoalResource
    {
        return new MilkGoalResource($action->handle($milkGoal));
    }

    public function update(UpdateMilkGoalRequest $request, MilkGoal $milkGoal, UpdateMilkGoal $action): MilkGoalResource
    {
        /** @var int $goal */
        $goal = $request->validated('goal');

        return new MilkGoalResource($action->handle($milkGoal, $goal));
    }

    public function destroy(MilkGoal $milkGoal, DeleteMilkGoal $action): Response
    {
        $action->handle($milkGoal);

        return response()->noContent();
    }
}
