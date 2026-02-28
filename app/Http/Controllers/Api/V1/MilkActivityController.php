<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Actions\MilkActivity\GetMilkActivity;
use App\Http\Requests\Api\V1\MilkActivityRequest;
use App\Http\Resources\MilkActivityCollection;
use App\Models\Baby;

final readonly class MilkActivityController
{
    public function __invoke(MilkActivityRequest $request, Baby $baby, GetMilkActivity $action): MilkActivityCollection
    {
        /** @var string $period */
        $period = $request->validated('period');

        return new MilkActivityCollection($action->handle($baby, $period));
    }
}
