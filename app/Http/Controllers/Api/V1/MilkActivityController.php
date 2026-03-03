<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Actions\MilkActivity\GetMilkActivity;
use App\Enums\MilkActivity\Period;
use App\Http\Requests\Api\V1\MilkActivityRequest;
use App\Http\Resources\MilkActivityCollection;
use App\Models\Baby;

final readonly class MilkActivityController
{
    public function __invoke(MilkActivityRequest $request, Baby $baby, GetMilkActivity $action): MilkActivityCollection
    {
        $period = $request->enum('period', Period::class) ?? Period::Week;

        return new MilkActivityCollection($action->handle($baby, $period));
    }
}
