<?php

declare(strict_types=1);

namespace App\Actions\Baby;

use App\Models\Baby;
use Illuminate\Database\Eloquent\Collection;

final readonly class ListBabies
{
    /**
     * @return Collection<int, Baby>
     */
    public function handle(): Collection
    {
        return Baby::query()->orderBy('id')->get();
    }
}
