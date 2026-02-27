<?php

declare(strict_types=1);

namespace App\Models;

use Carbon\CarbonInterface;
use Database\Factories\MilkMeasureFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

/**
 * @property-read int $id
 * @property-read string $uuid
 * @property-read int $milk_goal_id
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

    /** @var list<string> */
    protected $with = ['milkGoal'];

    public function getRouteKeyName(): string
    {
        return 'uuid';
    }

    /**
     * @return array<string, string>
     */
    public function casts(): array
    {
        return [
            'milk_goal_id' => 'integer',
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

    protected static function boot(): void
    {
        parent::boot();

        self::creating(function (MilkMeasure $measure): void {
            if (empty($measure->uuid)) {
                $measure->uuid = (string) Str::uuid();
            }
        });
    }
}
