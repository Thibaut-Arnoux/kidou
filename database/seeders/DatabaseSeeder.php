<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\Baby;
use App\Models\MilkGoal;
use App\Models\MilkMeasure;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Database\Seeder;

final class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        if (app()->isProduction()) {
            return;
        }

        $user = User::factory()->create([
            'name' => 'kidou',
            'email' => 'admin@admin.com',
            'password' => 'admin',
            'remember_token' => null,
            'two_factor_secret' => null,
            'two_factor_recovery_codes' => null,
            'two_factor_confirmed_at' => null,
        ]);

        $bobby = Baby::factory()->create([
            'user_id' => $user->id,
            'nickname' => 'Bobby',
        ]);

        $startDate = CarbonImmutable::now()->subDays(44);
        $daysToSkip = collect(range(0, 44))->random(random_int(5, 8))->all();

        for ($day = 0; $day < 45; $day++) {
            if (in_array($day, $daysToSkip, true)) {
                continue;
            }

            $date = $startDate->addDays($day);

            $milkGoal = MilkGoal::factory()->create([
                'baby_id' => $bobby->id,
                'date' => $date->toDateString(),
                'goal' => 800,
            ]);

            $measureCount = random_int(0, 20);

            if ($measureCount > 0) {
                MilkMeasure::factory()->count($measureCount)->create([
                    'milk_goal_id' => $milkGoal->id,
                    'measured_at' => fn () => $date->setTime(random_int(6, 23), random_int(0, 59)),
                ]);
            }
        }
    }
}
