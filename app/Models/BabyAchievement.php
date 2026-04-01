<?php

declare(strict_types=1);

namespace App\Models;

use App\Observers\BabyAchievementObserver;
use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\Pivot;

/**
 * @property-read int $id
 * @property string $uuid
 * @property-read int $baby_id
 * @property-read int $achievement_id
 * @property-read string $achievement_uuid
 * @property string|null $note
 * @property-read CarbonInterface $created_at
 * @property-read CarbonInterface $updated_at
 * @property-read Achievement $achievement
 */
#[ObservedBy(BabyAchievementObserver::class)]
final class BabyAchievement extends Pivot
{
    public $incrementing = true;

    protected $table = 'baby_achievement';

    /** @var list<string> */
    protected $fillable = [
        'baby_id',
        'achievement_id',
        'note',
    ];

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
            'baby_id' => 'integer',
            'achievement_id' => 'integer',
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
        ];
    }

    /**
     * @return BelongsTo<Achievement, $this>
     */
    public function achievement(): BelongsTo
    {
        return $this->belongsTo(Achievement::class);
    }
}
