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
use InvalidArgumentException;

final readonly class GetMilkActivity
{
    /**
     * @return Collection<int, TrendItem>
     */
    public function handle(Baby $baby, string $period): Collection
    {
        $milkGoalIds = $baby->milkGoals()->select('id');

        $endOfDay = Date::now()->endOfDay();

        [$from, $to] = match ($period) {
            'week' => [Date::now()->subWeek()->startOfDay(), $endOfDay],
            'month' => [Date::now()->subMonth()->startOfDay(), $endOfDay],
            'year' => [Date::now()->subYear()->startOfDay(), $endOfDay],
            default => throw new InvalidArgumentException('Invalid period: '.$period),
        };

        $baseQuery = MilkMeasure::query()
            ->whereIn('milk_goal_id', $milkGoalIds)
            ->without('milkGoal');

        $interval = $period === 'year' ? 'month' : 'day';

        /** @var Collection<int, TrendValue> $sums */
        $sums = Trend::query(clone $baseQuery)
            ->dateColumn('measured_at')
            ->between(start: $from, end: $to)
            ->interval($interval)
            ->sum('value');

        /** @var Collection<int, TrendValue> $counts */
        $counts = Trend::query(clone $baseQuery)
            ->dateColumn('measured_at')
            ->between(start: $from, end: $to)
            ->interval($interval)
            ->count();

        /** @var Collection<int, TrendValue> $goals */
        $goals = Trend::query(MilkGoal::query()->whereIn('id', $milkGoalIds))
            ->dateColumn('date')
            ->dateAlias('period')
            ->between(start: $from, end: $to)
            ->interval($interval)
            ->sum('goal');

        /** @var Collection<string, TrendValue> $countsKeyed */
        $countsKeyed = $counts->keyBy(fn (TrendValue $v): string => $v->date);
        /** @var Collection<string, TrendValue> $goalsKeyed */
        $goalsKeyed = $goals->keyBy(fn (TrendValue $v): string => $v->date);

        return $sums->map(function (TrendValue $v) use ($countsKeyed, $goalsKeyed): TrendItem {
            $measureValue = $v->aggregate;
            $measureCount = $countsKeyed->get($v->date)?->aggregate;
            $goalValue = $goalsKeyed->get($v->date)?->aggregate;

            assert(is_int($measureValue));
            assert(is_int($measureCount));
            assert(is_int($goalValue));

            return new TrendItem(
                date: $v->date,
                measureValue: $measureValue,
                measureCount: $measureCount,
                goalValue: $goalValue,
            );
        })->values();
    }
}
