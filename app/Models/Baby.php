<?php

declare(strict_types=1);

namespace App\Models;

use App\Observers\BabyObserver;
use Carbon\CarbonInterface;
use Database\Factories\BabyFactory;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @property-read int $id
 * @property string $uuid
 * @property-read int $user_id
 * @property-read string $nickname
 * @property-read CarbonInterface $created_at
 * @property-read CarbonInterface $updated_at
 * @property-read User $user
 * @property-read Collection<int, MilkGoal> $milkGoals
 * @property-read Collection<int, Achievement> $achievements
 */
#[ObservedBy(BabyObserver::class)]
final class Baby extends Model
{
    /** @use HasFactory<BabyFactory> */
    use HasFactory;

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
            'user_id' => 'integer',
            'nickname' => 'string',
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
        ];
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * @return HasMany<MilkGoal, $this>
     */
    public function milkGoals(): HasMany
    {
        return $this->hasMany(MilkGoal::class);
    }

    /**
     * @return BelongsToMany<Achievement, $this>
     */
    public function achievements(): BelongsToMany
    {
        return $this->belongsToMany(Achievement::class, 'baby_achievement')
            ->using(BabyAchievement::class)
            ->as('link')
            ->withPivot('uuid', 'note')
            ->withTimestamps();
    }
}
