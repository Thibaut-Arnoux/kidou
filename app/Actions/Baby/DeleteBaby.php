<?php

declare(strict_types=1);

namespace App\Actions\Baby;

use App\Models\Baby;

final readonly class DeleteBaby
{
    public function handle(Baby $baby): void
    {
        $baby->delete();
    }
}
