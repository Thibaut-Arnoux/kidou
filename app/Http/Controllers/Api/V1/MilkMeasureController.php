<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Actions\MilkMeasure\CreateMilkMeasure;
use App\Actions\MilkMeasure\DeleteMilkMeasure;
use App\Actions\MilkMeasure\ListMilkMeasures;
use App\Actions\MilkMeasure\ShowMilkMeasure;
use App\Actions\MilkMeasure\UpdateMilkMeasure;
use App\Http\Requests\Api\V1\StoreMilkMeasureRequest;
use App\Http\Requests\Api\V1\UpdateMilkMeasureRequest;
use App\Http\Resources\MilkMeasureResource;
use App\Models\MilkGoal;
use App\Models\MilkMeasure;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\Date;
use Symfony\Component\HttpFoundation\Response;

final readonly class MilkMeasureController
{
    public function index(MilkGoal $milkGoal, ListMilkMeasures $action): AnonymousResourceCollection
    {
        return MilkMeasureResource::collection($action->handle($milkGoal));
    }

    public function store(StoreMilkMeasureRequest $request, MilkGoal $milkGoal, CreateMilkMeasure $action): JsonResponse
    {
        /** @var int $value */
        $value = $request->validated('value');

        /** @var string $measuredAt */
        $measuredAt = $request->validated('measured_at');

        /** @var ?string $uuid */
        $uuid = $request->validated('uuid');

        $measure = $action->handle($milkGoal, $value, Date::parse($measuredAt), $uuid);
        $measure->setAttribute('milk_goal_uuid', $milkGoal->uuid);

        return MilkMeasureResource::make($measure)
            ->response()
            ->setStatusCode(Response::HTTP_CREATED);
    }

    public function show(MilkGoal $milkGoal, MilkMeasure $measure, ShowMilkMeasure $action): MilkMeasureResource
    {
        return new MilkMeasureResource($action->handle($measure));
    }

    public function update(UpdateMilkMeasureRequest $request, MilkGoal $milkGoal, MilkMeasure $measure, UpdateMilkMeasure $action): MilkMeasureResource
    {
        /** @var int $value */
        $value = $request->validated('value');

        return new MilkMeasureResource($action->handle($measure, $value));
    }

    public function destroy(MilkGoal $milkGoal, MilkMeasure $measure, DeleteMilkMeasure $action): Response
    {
        $action->handle($measure);

        return response()->noContent();
    }
}
