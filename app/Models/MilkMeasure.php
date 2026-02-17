<?php

declare(strict_types=1);

namespace App\Models;

use Carbon\CarbonInterface;
use Database\Factories\MilkMeasureFactory;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property-read string $id
 * @property-read string $milk_goal_id
 * @property int $value
 * @property CarbonInterface $measured_at
 * @property-read CarbonInterface $created_at
 * @property-read CarbonInterface $updated_at
 * @property-read MilkGoal $milkGoal
 */
final class MilkMeasure extends Model
{
    /** @use HasFactory<MilkMeasureFactory> */
    use HasFactory;

    use HasUuids;

    /**
     * @return array<string, string>
     */
    public function casts(): array
    {
        return [
            'milk_goal_id' => 'string',
            'value' => 'integer',
            'measured_at' => 'datetime',
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
        ];
    }

    /**
     * @return BelongsTo<MilkGoal, $this>
     */
    public function milkGoal(): BelongsTo
    {
        return $this->belongsTo(MilkGoal::class);
    }
}
