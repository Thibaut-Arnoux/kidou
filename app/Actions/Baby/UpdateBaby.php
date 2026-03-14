<?php

declare(strict_types=1);

namespace App\Actions\Baby;

use App\Models\Baby;

final readonly class UpdateBaby
{
    public function handle(Baby $baby, string $nickname): Baby
    {
        $baby->update(['nickname' => $nickname]);

        return $baby;
    }
}
