<?php

declare(strict_types=1);

namespace App\Observers;

use App\Models\Baby;
use Illuminate\Support\Str;

final class BabyObserver
{
    public function creating(Baby $baby): void
    {
        if (empty($baby->uuid)) {
            $baby->uuid = (string) Str::uuid();
        }
    }
}
