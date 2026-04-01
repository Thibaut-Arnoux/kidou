<?php

declare(strict_types=1);

namespace App\Actions\MilkActivity;

use App\Enums\MilkActivity\Period;
use App\Models\Baby;
use App\Models\MilkGoal;
use App\Models\MilkMeasure;
use App\ValueObjects\MilkActivity\TrendItem;
use Flowframe\Trend\Trend;
use Flowframe\Trend\TrendValue;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Date;

final readonly class GetMilkActivity
{
    /**
     * @return Collection<int, TrendItem>
     */
    public function handle(Baby $baby, Period $period): Collection
    {
        $milkGoalIds = $baby->milkGoals()->select('id');

        $endOfDay = Date::now()->endOfDay();

        [$from, $to] = match ($period) {
            Period::Week => [Date::now()->subWeek()->addDay()->startOfDay(), $endOfDay],
            Period::Month => [Date::now()->subMonth()->addDay()->startOfDay(), $endOfDay],
            Period::Year => [Date::now()->subYear()->addMonth()->startOfMonth()->startOfDay(), $endOfDay],
        };

        $interval = $period->interval();

        $measureTrend = fn (): Trend => Trend::query(
            MilkMeasure::query()
                ->whereIn('milk_goal_id', $milkGoalIds)

        )
            ->dateColumn('measured_at')
            ->between(start: $from, end: $to)
            ->interval($interval);

        /** @var Collection<int, TrendValue> $sums */
        $sums = $measureTrend()->sum('value');

        /** @var Collection<int, TrendValue> $counts */
        $counts = $measureTrend()->count();

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
            /** @var int $measureValue */
            $measureValue = $v->aggregate;
            /** @var int $measureCount */
            $measureCount = $countsKeyed->get($v->date)->aggregate ?? 0;
            /** @var int $goalValue */
            $goalValue = $goalsKeyed->get($v->date)->aggregate ?? 0;

            return new TrendItem(
                date: $v->date,
                measureValue: $measureValue,
                measureCount: $measureCount,
                goalValue: $goalValue,
            );
        })->values();
    }
}
