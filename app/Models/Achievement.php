<?php

declare(strict_types=1);

namespace App\Models;

use App\Observers\AchievementObserver;
use Carbon\CarbonInterface;
use Database\Factories\AchievementFactory;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property-read int $id
 * @property string $uuid
 * @property-read int $category_id
 * @property-read string $category_uuid
 * @property string $name
 * @property string|null $description
 * @property int|null $expected_age_min_months
 * @property int|null $expected_age_max_months
 * @property-read CarbonInterface $created_at
 * @property-read CarbonInterface $updated_at
 * @property-read Category $category
 */
#[ObservedBy(AchievementObserver::class)]
final class Achievement extends Model
{
    /** @use HasFactory<AchievementFactory> */
    use HasFactory;

    /** @var list<string> */
    protected $fillable = [
        'category_id',
        'name',
        'description',
        'expected_age_min_months',
        'expected_age_max_months',
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
            'category_id' => 'integer',
            'name' => 'string',
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
        ];
    }

    /**
     * @return BelongsTo<Category, $this>
     */
    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }
}
