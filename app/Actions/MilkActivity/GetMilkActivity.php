<?php

declare(strict_types=1);

namespace App\Actions\MilkActivity;

use App\Models\Baby;
use App\Models\MilkGoal;
use App\Models\MilkMeasure;
use Flowframe\Trend\Trend;
use Flowframe\Trend\TrendValue;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Date;

final readonly class GetMilkActivity
{
    /**
     * @return Collection<int, TrendItem>
     */
    public function handle(Baby $baby, string $period): Collection
    {
        $milkGoalIds = $baby->milkGoals()->select('id');

        [$from, $to] = match ($period) {
            'week' => [Date::now()->subWeek(), Date::now()],
            'month' => [Date::now()->subMonth(), Date::now()],
            'year' => [Date::now()->subYear(), Date::now()],
        };

        $baseQuery = MilkMeasure::query()
            ->whereIn('milk_goal_id', $milkGoalIds)
            ->without('milkGoal');

        // Query 1: sum of measure values per day
        $sums = Trend::query(clone $baseQuery)
            ->dateColumn('measured_at')
            ->between(start: $from, end: $to)
            ->perDay()
            ->sum('value');

        // Query 2: count of measures per day
        $counts = Trend::query(clone $baseQuery)
            ->dateColumn('measured_at')
            ->between(start: $from, end: $to)
            ->perDay()
            ->count();

        // Query 3: goal value per day
        $goals = Trend::query(MilkGoal::query()->whereIn('id', $milkGoalIds))
            ->dateColumn('date')
            ->between(start: $from, end: $to)
            ->perDay()
            ->sum('goal');

        // Zip into TrendItem collection keyed by date
        $countsKeyed = $counts->keyBy(fn (TrendValue $v): string => $v->date);
        $goalsKeyed = $goals->keyBy(fn (TrendValue $v): string => $v->date);

        return $sums->map(fn (TrendValue $v): TrendItem => new TrendItem(
            date: $v->date,
            measureValue: $v->aggregate,
            measureCount: $countsKeyed->get($v->date)->aggregate,
            goalValue: $goalsKeyed->get($v->date)->aggregate,
        ))->values();
    }
}
