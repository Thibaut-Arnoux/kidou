<?php

declare(strict_types=1);

namespace App\Actions\MilkGoal;

use App\Models\Baby;
use Illuminate\Contracts\Pagination\CursorPaginator;

final readonly class ListMilkGoals
{
    /**
     * @return CursorPaginator<int, \App\Models\MilkGoal>
     */
    public function handle(Baby $baby): CursorPaginator
    {
        return $baby->milkGoals()
            ->latest('date')
            ->cursorPaginate();
    }
}
