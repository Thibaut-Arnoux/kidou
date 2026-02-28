<?php

declare(strict_types=1);

namespace App\Http\Resources;

use App\Actions\MilkActivity\TrendItem;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\ResourceCollection;
use Illuminate\Support\Collection;

final class MilkActivityCollection extends ResourceCollection
{
    /** @var Collection<int, TrendItem> */
    private readonly Collection $trends;

    /**
     * @param  Collection<int, TrendItem>  $trends
     */
    public function __construct(Collection $trends)
    {
        $this->trends = $trends;

        parent::__construct($this->trends);
    }

    /**
     * @return list<array{date: string, measure_value: int, measure_count: int, goal_value: int}>
     */
    public function toArray(Request $request): array
    {
        return $this->trends->map(fn (TrendItem $item): array => [
            'date' => $item->date,
            'measure_value' => $item->measureValue,
            'measure_count' => $item->measureCount,
            'goal_value' => $item->goalValue,
        ])->all();
    }

    /**
     * @return array{meta: array{measure_total: int, measure_total_count: int, measure_average: int, goal_count: int, goal_reached_count: int, goal_reached_rate: int|float}}
     */
    public function with(Request $request): array
    {
        $measureTotal = $this->trends->sum(fn (TrendItem $item): int => $item->measureValue);
        $measureCount = $this->trends->sum(fn (TrendItem $item): int => $item->measureCount);

        $withGoal = $this->trends->filter(fn (TrendItem $item): bool => $item->goalValue > 0);
        $goalCount = $withGoal->count();
        $goalReachedCount = $withGoal
            ->filter(fn (TrendItem $item): bool => $item->measureValue >= $item->goalValue)
            ->count();

        return [
            'meta' => [
                'measure_total' => $measureTotal,
                'measure_total_count' => $measureCount,
                'measure_average' => $measureCount > 0 ? (int) round($measureTotal / $measureCount) : 0,
                'goal_count' => $goalCount,
                'goal_reached_count' => $goalReachedCount,
                'goal_reached_rate' => $goalCount > 0 ? round($goalReachedCount / $goalCount, 2) : 0.0,
            ],
        ];
    }
}
