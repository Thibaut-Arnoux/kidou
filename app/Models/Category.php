<?php

declare(strict_types=1);

namespace App\Models;

use App\Observers\CategoryObserver;
use Carbon\CarbonInterface;
use Database\Factories\CategoryFactory;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @property-read int $id
 * @property string $uuid
 * @property string $name
 * @property string $slug
 * @property-read CarbonInterface $created_at
 * @property-read CarbonInterface $updated_at
 * @property-read Collection<int, Achievement> $achievements
 */
#[ObservedBy(CategoryObserver::class)]
final class Category extends Model
{
    /** @use HasFactory<CategoryFactory> */
    use HasFactory;

    /** @var list<string> */
    protected $fillable = [
        'name',
        'slug',
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
            'name' => 'string',
            'slug' => 'string',
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
        ];
    }

    /**
     * @return HasMany<Achievement, $this>
     */
    public function achievements(): HasMany
    {
        return $this->hasMany(Achievement::class);
    }
}
