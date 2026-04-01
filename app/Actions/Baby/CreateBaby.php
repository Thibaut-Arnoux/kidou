<?php

declare(strict_types=1);

namespace App\Actions\Baby;

use App\Models\Baby;
use App\Models\User;

final readonly class CreateBaby
{
    public function handle(User $user, string $nickname): Baby
    {
        /** @var Baby */
        return Baby::query()->create([
            'user_id' => $user->id,
            'nickname' => $nickname,
        ]);
    }
}
