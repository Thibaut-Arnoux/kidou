<?php

declare(strict_types=1);

namespace App\Actions\MilkMeasure;

use App\Models\MilkGoal;
use Illuminate\Database\Eloquent\Collection;

final readonly class ListMilkMeasures
{
    /**
     * @return Collection<int, \App\Models\MilkMeasure>
     */
    public function handle(MilkGoal $milkGoal): Collection
    {
        return $milkGoal->measures()->withAggregate('milkGoal', 'uuid')->get();
    }
}
