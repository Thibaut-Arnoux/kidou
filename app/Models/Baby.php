<?php

declare(strict_types=1);

namespace App\Models;

use Carbon\CarbonInterface;
use Database\Factories\BabyFactory;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

/**
 * @property-read int $id
 * @property-read string $uuid
 * @property-read int $user_id
 * @property-read string $nickname
 * @property-read CarbonInterface $created_at
 * @property-read CarbonInterface $updated_at
 * @property-read User $user
 * @property-read Collection<int, MilkGoal> $milkGoals
 */
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

    protected static function boot(): void
    {
        parent::boot();

        self::creating(function (Baby $baby): void {
            if (empty($baby->uuid)) {
                $baby->uuid = (string) Str::uuid();
            }
        });
    }
}
