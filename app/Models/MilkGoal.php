<?php

declare(strict_types=1);

namespace App\Models;

use Carbon\CarbonInterface;
use Database\Factories\MilkGoalFactory;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @property-read string $id
 * @property-read string $baby_id
 * @property-read CarbonInterface $date
 * @property int $goal
 * @property-read CarbonInterface $created_at
 * @property-read CarbonInterface $updated_at
 * @property-read Baby $baby
 * @property-read Collection<int, MilkMeasure> $measures
 */
final class MilkGoal extends Model
{
    /** @use HasFactory<MilkGoalFactory> */
    use HasFactory;

    use HasUuids;

    /**
     * @return array<string, string>
     */
    public function casts(): array
    {
        return [
            'baby_id' => 'string',
            'date' => 'date:Y-m-d',
            'goal' => 'integer',
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
        ];
    }

    /**
     * @return BelongsTo<Baby, $this>
     */
    public function baby(): BelongsTo
    {
        return $this->belongsTo(Baby::class);
    }

    /**
     * @return HasMany<MilkMeasure, $this>
     */
    public function measures(): HasMany
    {
        return $this->hasMany(MilkMeasure::class);
    }
}
