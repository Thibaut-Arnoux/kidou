# Achievements Refactor Implementation Plan

> **For agentic workers:** REQUIRED: Use superpowers:subagent-driven-development (if subagents available) or superpowers:executing-plans to implement this plan. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Simplify achievements by removing custom achievements, flattening API routes, replacing `achieved_at` with `created_at`, and splitting controllers into focused invokable/resource controllers.

**Architecture:** Flat REST endpoints for categories, achievements, and baby-achievements. Categories and achievements are read-only lists with optional `?category={uuid}` filter. BabyAchievement is a full resource controller (index/store/update/destroy). All custom achievement logic removed. Actions use Eloquent relations instead of raw queries.

**Tech Stack:** Laravel 12, Pest 4, PHP 8.5

---

## File Structure

### Files to CREATE
- `app/Http/Controllers/Api/V1/ListCategoryController.php` — invokable, lists all categories
- `app/Http/Controllers/Api/V1/ListAchievementController.php` — invokable, lists achievements with optional category filter
- `app/Http/Controllers/Api/V1/BabyAchievementController.php` — resource (index/store/update/destroy)
- `app/Http/Requests/Api/V1/StoreBabyAchievementRequest.php` — validates `achievement_id`, optional `note`, optional `uuid`
- `app/Http/Requests/Api/V1/UpdateBabyAchievementRequest.php` — validates optional `note`
- `app/Actions/BabyAchievement/ListBabyAchievements.php` — list baby-achievements with optional category filter
- `app/Actions/BabyAchievement/CreateBabyAchievement.php` — attach achievement to baby
- `app/Actions/BabyAchievement/UpdateBabyAchievement.php` — update note on baby-achievement
- `app/Actions/BabyAchievement/DeleteBabyAchievement.php` — detach achievement from baby
- `app/Actions/Achievement/ListAchievements.php` — list achievements with optional category filter
- `database/migrations/2026_03_12_120000_simplify_achievements_schema.php` — drop `is_custom`, `user_id`, `achieved_at`
- `tests/Feature/Api/V1/ListCategoryTest.php` — tests for GET /v1/categories
- `tests/Feature/Api/V1/ListAchievementTest.php` — tests for GET /v1/achievements
- `tests/Feature/Api/V1/BabyAchievementTest.php` — tests for CRUD /v1/baby-achievements

### Files to MODIFY
- `app/Actions/Category/ListCategories.php` — simplified (just list, no counts)
- `routes/api/v1.php` — replace all achievement routes
- `app/Models/Achievement.php` — remove `user_id`, `user()`, scopes
- `app/Models/Category.php` — remove `is_custom`
- `app/Models/BabyAchievement.php` — remove `achieved_at`, add `getRouteKeyName`, add `achievement()` and `baby()` relations
- `app/Models/Baby.php` — simplify `achievements()` pivot (remove `achieved_at`)
- `app/Http/Resources/AchievementResource.php` — remove link data, add `category_id`
- `app/Http/Resources/BabyAchievementResource.php` — replace `achieved_at` with `created_at`, add `achievement_id`
- `app/Http/Resources/CategoryResource.php` — remove `is_custom`, progress counts
- `database/factories/CategoryFactory.php` — remove `custom()` state, `is_custom`
- `database/factories/AchievementFactory.php` — remove `customFor()` state, `user_id`
- `database/seeders/CategorySeeder.php` — remove Custom category, remove `is_custom`
- `database/seeders/AchievementSeeder.php` — remove `user_id` from seeds

### Files to DELETE
- `app/Http/Controllers/Api/V1/AchievementController.php`
- `app/Http/Controllers/Api/V1/AchievementLinkController.php`
- `app/Http/Controllers/Api/V1/CategoryController.php`
- `app/Http/Controllers/Api/V1/CategoryAchievementController.php`
- `app/Http/Requests/Api/V1/StoreAchievementRequest.php`
- `app/Http/Requests/Api/V1/StoreAchievementLinkRequest.php`
- `app/Http/Requests/Api/V1/UpdateAchievementLinkRequest.php`
- `app/Actions/Achievement/CreateAchievement.php`
- `app/Actions/Achievement/DeleteAchievement.php`
- `app/Actions/AchievementLink/LinkAchievement.php`
- `app/Actions/AchievementLink/UnlinkAchievement.php`
- `app/Actions/AchievementLink/UpdateAchievementLink.php`
- `app/Actions/CategoryAchievement/ListCategoryAchievements.php`
- `tests/Feature/Api/V1/AchievementTest.php`
- `tests/Feature/Api/V1/AchievementLinkTest.php`
- `tests/Feature/Api/V1/CategoryTest.php`
- `tests/Feature/Api/V1/CategoryAchievementTest.php`

---

## Chunk 1: Schema Migration & Model Cleanup

### Task 1: Create migration to simplify schema

**Files:**
- Create: `database/migrations/2026_03_12_120000_simplify_achievements_schema.php`

- [ ] **Step 1: Create migration**

Run: `vendor/bin/sail artisan make:migration simplify_achievements_schema --no-interaction`

- [ ] **Step 2: Write migration content**

```php
public function up(): void
{
    Schema::table('categories', function (Blueprint $table): void {
        $table->dropColumn('is_custom');
    });

    Schema::table('achievements', function (Blueprint $table): void {
        $table->dropForeign(['user_id']);
        $table->dropColumn('user_id');
    });

    Schema::table('baby_achievement', function (Blueprint $table): void {
        $table->dropColumn('achieved_at');
    });
}

public function down(): void
{
    Schema::table('baby_achievement', function (Blueprint $table): void {
        $table->dateTime('achieved_at')->after('achievement_id');
    });

    Schema::table('achievements', function (Blueprint $table): void {
        $table->foreignId('user_id')->nullable()->after('category_id')->constrained()->cascadeOnDelete();
    });

    Schema::table('categories', function (Blueprint $table): void {
        $table->boolean('is_custom')->default(false)->after('slug');
    });
}
```

- [ ] **Step 3: Run migration**

Run: `vendor/bin/sail artisan migrate`
Expected: Migration runs successfully.

- [ ] **Step 4: Commit**

```
refactor(achievements): drop is_custom, user_id, achieved_at columns
```

### Task 2: Update models

**Files:**
- Modify: `app/Models/Category.php`
- Modify: `app/Models/Achievement.php`
- Modify: `app/Models/BabyAchievement.php`
- Modify: `app/Models/Baby.php`

- [ ] **Step 1: Simplify Category model**

Remove `is_custom` from `$fillable`, `casts()`, and PHPDoc.

```php
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
```

- [ ] **Step 2: Simplify Achievement model**

Remove `user_id` from `$fillable`, remove `user()` relation, remove `predefined()` and `customForUser()` scopes, remove all related imports.

```php
/**
 * @property-read int $id
 * @property string $uuid
 * @property-read int $category_id
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

    /**
     * @return BelongsToMany<Baby, $this>
     */
    public function babies(): BelongsToMany
    {
        return $this->belongsToMany(Baby::class, 'baby_achievement')
            ->using(BabyAchievement::class)
            ->as('link')
            ->withPivot('uuid', 'note')
            ->withTimestamps();
    }
}
```

- [ ] **Step 3: Update BabyAchievement model**

Remove `achieved_at` from `$fillable` and `casts()`. Add `getRouteKeyName()`, `achievement()` and `baby()` relations. Add `use Illuminate\Database\Eloquent\Relations\BelongsTo;` import.

```php
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property-read int $id
 * @property string $uuid
 * @property-read int $baby_id
 * @property-read int $achievement_id
 * @property string|null $note
 * @property-read CarbonInterface $created_at
 * @property-read CarbonInterface $updated_at
 * @property-read Achievement $achievement
 * @property-read Baby $baby
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

    /**
     * @return BelongsTo<Baby, $this>
     */
    public function baby(): BelongsTo
    {
        return $this->belongsTo(Baby::class);
    }
}
```

- [ ] **Step 4: Update Baby model achievements relation**

Remove `achieved_at` from `withPivot`.

```php
public function achievements(): BelongsToMany
{
    return $this->belongsToMany(Achievement::class, 'baby_achievement')
        ->using(BabyAchievement::class)
        ->as('link')
        ->withPivot('uuid', 'note')
        ->withTimestamps();
}
```

- [ ] **Step 5: Run Pint**

Run: `vendor/bin/sail bin pint --dirty --format agent`

- [ ] **Step 6: Commit**

```
refactor(achievements): simplify models by removing custom achievement support
```

### Task 3: Update factories and seeders

**Files:**
- Modify: `database/factories/CategoryFactory.php`
- Modify: `database/factories/AchievementFactory.php`
- Modify: `database/seeders/CategorySeeder.php`
- Modify: `database/seeders/AchievementSeeder.php`

- [ ] **Step 1: Simplify CategoryFactory**

Remove `is_custom` from `definition()` and remove `custom()` state entirely.

```php
final class CategoryFactory extends Factory
{
    /** @return array<string, mixed> */
    public function definition(): array
    {
        $name = fake()->unique()->words(2, true);

        return [
            'name' => $name,
            'slug' => str($name)->slug()->toString(),
        ];
    }
}
```

- [ ] **Step 2: Simplify AchievementFactory**

Remove `user_id` from `definition()` and remove `customFor()` state entirely.

```php
final class AchievementFactory extends Factory
{
    /** @return array<string, mixed> */
    public function definition(): array
    {
        $minAge = fake()->numberBetween(0, 30);

        return [
            'category_id' => Category::factory(),
            'name' => fake()->sentence(3),
            'description' => fake()->sentence(),
            'expected_age_min_months' => $minAge,
            'expected_age_max_months' => fake()->numberBetween($minAge, 36),
        ];
    }
}
```

- [ ] **Step 3: Simplify CategorySeeder**

Remove "Custom" category and `is_custom` field.

```php
public function run(): void
{
    $categories = [
        ['name' => 'Motor Skills', 'slug' => 'motor-skills'],
        ['name' => 'Language', 'slug' => 'language'],
        ['name' => 'Social & Emotional', 'slug' => 'social-emotional'],
        ['name' => 'Cognitive', 'slug' => 'cognitive'],
        ['name' => 'Self-Care', 'slug' => 'self-care'],
    ];

    foreach ($categories as $category) {
        Category::query()->firstOrCreate(
            ['slug' => $category['slug']],
            $category,
        );
    }
}
```

- [ ] **Step 4: Simplify AchievementSeeder**

Remove `'user_id' => null` from the create call.

Change line 92:
```php
// Before:
[...$achievement, 'user_id' => null],
// After:
$achievement,
```

- [ ] **Step 5: Run Pint**

Run: `vendor/bin/sail bin pint --dirty --format agent`

- [ ] **Step 6: Commit**

```
refactor(achievements): simplify factories and seeders
```

---

## Chunk 2: Delete Old Code

### Task 4: Delete old controllers, actions, requests, and tests

**Files to delete:**
- `app/Http/Controllers/Api/V1/AchievementController.php`
- `app/Http/Controllers/Api/V1/AchievementLinkController.php`
- `app/Http/Controllers/Api/V1/CategoryController.php`
- `app/Http/Controllers/Api/V1/CategoryAchievementController.php`
- `app/Http/Requests/Api/V1/StoreAchievementRequest.php`
- `app/Http/Requests/Api/V1/StoreAchievementLinkRequest.php`
- `app/Http/Requests/Api/V1/UpdateAchievementLinkRequest.php`
- `app/Actions/Achievement/CreateAchievement.php`
- `app/Actions/Achievement/DeleteAchievement.php`
- `app/Actions/AchievementLink/LinkAchievement.php`
- `app/Actions/AchievementLink/UnlinkAchievement.php`
- `app/Actions/AchievementLink/UpdateAchievementLink.php`
- `app/Actions/CategoryAchievement/ListCategoryAchievements.php`
- `tests/Feature/Api/V1/AchievementTest.php`
- `tests/Feature/Api/V1/AchievementLinkTest.php`
- `tests/Feature/Api/V1/CategoryTest.php`
- `tests/Feature/Api/V1/CategoryAchievementTest.php`

- [ ] **Step 1: Delete all files listed above**

Run: `git rm` on each file.

- [ ] **Step 2: Clean up empty directories**

Remove empty directories: `app/Actions/AchievementLink/`, `app/Actions/CategoryAchievement/`. Keep `app/Actions/Achievement/` (will be reused) and `app/Actions/Category/` (will be reused).

- [ ] **Step 3: Strip old routes from `routes/api/v1.php`**

Remove all achievement, achievement-link, and category routes from the `InjectDemoBaby` middleware group (the routes using `AchievementController`, `AchievementLinkController`, `CategoryController`, `CategoryAchievementController`, and their imports). Keep the middleware group open for new routes.

- [ ] **Step 4: Commit**

```
refactor(achievements): delete old controllers, actions, requests, and tests
```

---

## Chunk 3: New List Endpoints (Categories + Achievements)

### Task 5: GET /v1/categories — ListCategoryController + tests

**Files:**
- Create: `app/Http/Controllers/Api/V1/ListCategoryController.php`
- Create: `app/Actions/Category/ListCategories.php`
- Modify: `app/Http/Resources/CategoryResource.php`
- Modify: `routes/api/v1.php`
- Create: `tests/Feature/Api/V1/ListCategoryTest.php`

- [ ] **Step 1: Write the test**

Create `tests/Feature/Api/V1/ListCategoryTest.php`:

```php
<?php

declare(strict_types=1);

use App\Models\Baby;
use App\Models\Category;
use App\Models\User;

beforeEach(function (): void {
    $this->user = User::factory()->create();
    $this->baby = Baby::factory()->for($this->user)->create();
    app()->instance(Baby::class, $this->baby);
});

it('lists all categories', function (): void {
    $categories = Category::factory()->count(3)->create();

    $this->getJson('/api/v1/categories')
        ->assertOk()
        ->assertJsonCount(3, 'data')
        ->assertJsonPath('data.0.id', $categories->first()->uuid)
        ->assertJsonPath('data.0.name', $categories->first()->name)
        ->assertJsonPath('data.0.slug', $categories->first()->slug);
});

it('returns empty list when no categories exist', function (): void {
    $this->getJson('/api/v1/categories')
        ->assertOk()
        ->assertJsonCount(0, 'data');
});
```

- [ ] **Step 2: Run test to verify it fails**

Run: `vendor/bin/sail artisan test --compact --filter=ListCategoryTest`
Expected: FAIL (routes/controller don't exist yet).

- [ ] **Step 3: Simplify CategoryResource**

```php
/** @mixin Category */
final class CategoryResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->uuid,
            'name' => $this->name,
            'slug' => $this->slug,
        ];
    }
}
```

- [ ] **Step 4: Rewrite ListCategories action**

Replace the existing `app/Actions/Category/ListCategories.php` entirely — remove the `$baby` and `$user` params, all the complex `withCount` queries, and simplify to a plain list.

```php
<?php

declare(strict_types=1);

namespace App\Actions\Category;

use App\Models\Category;
use Illuminate\Database\Eloquent\Collection;

final readonly class ListCategories
{
    /**
     * @return Collection<int, Category>
     */
    public function handle(): Collection
    {
        return Category::query()
            ->orderBy('id')
            ->get();
    }
}
```

- [ ] **Step 5: Create ListCategoryController**

```php
<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Actions\Category\ListCategories;
use App\Http\Resources\CategoryResource;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

final readonly class ListCategoryController
{
    public function __invoke(ListCategories $action): AnonymousResourceCollection
    {
        return CategoryResource::collection($action->handle());
    }
}
```

- [ ] **Step 6: Add route**

In `routes/api/v1.php`, inside the `InjectDemoBaby` middleware group:

```php
Route::get('categories', ListCategoryController::class)->name('api.v1.categories.index');
```

- [ ] **Step 7: Run test to verify it passes**

Run: `vendor/bin/sail artisan test --compact --filter=ListCategoryTest`
Expected: PASS

- [ ] **Step 8: Run Pint**

Run: `vendor/bin/sail bin pint --dirty --format agent`

- [ ] **Step 9: Commit**

```
feat(achievements): add GET /v1/categories endpoint
```

### Task 6: GET /v1/achievements — ListAchievementController + tests

**Files:**
- Create: `app/Http/Controllers/Api/V1/ListAchievementController.php`
- Create: `app/Actions/Achievement/ListAchievements.php`
- Modify: `app/Http/Resources/AchievementResource.php`
- Modify: `routes/api/v1.php`
- Create: `tests/Feature/Api/V1/ListAchievementTest.php`

- [ ] **Step 1: Write the test**

Create `tests/Feature/Api/V1/ListAchievementTest.php`:

```php
<?php

declare(strict_types=1);

use App\Models\Achievement;
use App\Models\Baby;
use App\Models\Category;
use App\Models\User;

beforeEach(function (): void {
    $this->user = User::factory()->create();
    $this->baby = Baby::factory()->for($this->user)->create();
    app()->instance(Baby::class, $this->baby);
});

it('lists all achievements', function (): void {
    $category = Category::factory()->create();
    Achievement::factory()->count(3)->for($category)->create();

    $this->getJson('/api/v1/achievements')
        ->assertOk()
        ->assertJsonCount(3, 'data');
});

it('filters achievements by category', function (): void {
    $category1 = Category::factory()->create();
    $category2 = Category::factory()->create();
    Achievement::factory()->count(2)->for($category1)->create();
    Achievement::factory()->count(3)->for($category2)->create();

    $this->getJson("/api/v1/achievements?category={$category1->uuid}")
        ->assertOk()
        ->assertJsonCount(2, 'data');
});

it('returns achievement fields correctly', function (): void {
    $category = Category::factory()->create();
    $achievement = Achievement::factory()->for($category)->create([
        'name' => 'Head Control',
        'description' => 'Holds head steady.',
        'expected_age_min_months' => 1,
        'expected_age_max_months' => 4,
    ]);

    $this->getJson('/api/v1/achievements')
        ->assertOk()
        ->assertJsonPath('data.0.id', $achievement->uuid)
        ->assertJsonPath('data.0.category_id', $category->uuid)
        ->assertJsonPath('data.0.name', 'Head Control')
        ->assertJsonPath('data.0.description', 'Holds head steady.')
        ->assertJsonPath('data.0.expected_age_min_months', 1)
        ->assertJsonPath('data.0.expected_age_max_months', 4);
});

it('returns 422 for invalid category uuid', function (): void {
    $this->getJson('/api/v1/achievements?category=not-a-uuid')
        ->assertUnprocessable();
});

it('returns 404 for non-existent category uuid', function (): void {
    $this->getJson('/api/v1/achievements?category=' . fake()->uuid())
        ->assertNotFound();
});
```

- [ ] **Step 2: Run test to verify it fails**

Run: `vendor/bin/sail artisan test --compact --filter=ListAchievementTest`
Expected: FAIL

- [ ] **Step 3: Update AchievementResource**

Remove `link` data, add `category_id` (as uuid).

```php
/** @mixin Achievement */
final class AchievementResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->uuid,
            'category_id' => $this->category->uuid,
            'name' => $this->name,
            'description' => $this->description,
            'expected_age_min_months' => $this->expected_age_min_months,
            'expected_age_max_months' => $this->expected_age_max_months,
        ];
    }
}
```

- [ ] **Step 4: Create ListAchievements action**

```php
<?php

declare(strict_types=1);

namespace App\Actions\Achievement;

use App\Models\Achievement;
use App\Models\Category;
use Illuminate\Database\Eloquent\Collection;

final readonly class ListAchievements
{
    /**
     * @return Collection<int, Achievement>
     */
    public function handle(?Category $category = null): Collection
    {
        return Achievement::query()
            ->with('category')
            ->when($category, fn ($query) => $query->where('category_id', $category->id))
            ->orderBy('id')
            ->get();
    }
}
```

- [ ] **Step 5: Create ListAchievementController**

> Note: The `?category=` filter uses inline validation rather than a FormRequest. This is intentional — a single optional query param filter doesn't warrant a dedicated FormRequest class. The same pattern is used in `BabyAchievementController@index`.

```php
<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Actions\Achievement\ListAchievements;
use App\Http\Resources\AchievementResource;
use App\Models\Category;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

final readonly class ListAchievementController
{
    public function __invoke(Request $request, ListAchievements $action): AnonymousResourceCollection
    {
        $category = null;

        if ($request->has('category')) {
            $request->validate(['category' => ['required', 'uuid']]);
            $category = Category::query()->where('uuid', $request->input('category'))->firstOrFail();
        }

        return AchievementResource::collection($action->handle($category));
    }
}
```

- [ ] **Step 6: Add route**

In `routes/api/v1.php`:

```php
Route::get('achievements', ListAchievementController::class)->name('api.v1.achievements.index');
```

- [ ] **Step 7: Run test to verify it passes**

Run: `vendor/bin/sail artisan test --compact --filter=ListAchievementTest`
Expected: PASS

- [ ] **Step 8: Run Pint**

Run: `vendor/bin/sail bin pint --dirty --format agent`

- [ ] **Step 9: Commit**

```
feat(achievements): add GET /v1/achievements endpoint with category filter
```

---

## Chunk 4: BabyAchievement CRUD

### Task 7: GET /v1/baby-achievements — index + tests

**Files:**
- Create: `app/Actions/BabyAchievement/ListBabyAchievements.php`
- Modify: `app/Http/Resources/BabyAchievementResource.php`
- Create: `app/Http/Controllers/Api/V1/BabyAchievementController.php` (start with index only)
- Modify: `routes/api/v1.php`
- Create: `tests/Feature/Api/V1/BabyAchievementTest.php`

- [ ] **Step 1: Write the test**

Create `tests/Feature/Api/V1/BabyAchievementTest.php`:

```php
<?php

declare(strict_types=1);

use App\Models\Achievement;
use App\Models\Baby;
use App\Models\BabyAchievement;
use App\Models\Category;
use App\Models\User;

beforeEach(function (): void {
    $this->user = User::factory()->create();
    $this->baby = Baby::factory()->for($this->user)->create();
    app()->instance(Baby::class, $this->baby);
    $this->category = Category::factory()->create();
});

it('lists all baby achievements', function (): void {
    $achievements = Achievement::factory()->count(2)->for($this->category)->create();

    foreach ($achievements as $achievement) {
        $this->baby->achievements()->attach($achievement, ['note' => 'test']);
    }

    $this->getJson('/api/v1/baby-achievements')
        ->assertOk()
        ->assertJsonCount(2, 'data');
});

it('filters baby achievements by category', function (): void {
    $category2 = Category::factory()->create();
    $a1 = Achievement::factory()->for($this->category)->create();
    $a2 = Achievement::factory()->for($category2)->create();

    $this->baby->achievements()->attach($a1, ['note' => null]);
    $this->baby->achievements()->attach($a2, ['note' => null]);

    $this->getJson("/api/v1/baby-achievements?category={$this->category->uuid}")
        ->assertOk()
        ->assertJsonCount(1, 'data');
});

it('returns baby achievement fields correctly', function (): void {
    $achievement = Achievement::factory()->for($this->category)->create();
    $this->baby->achievements()->attach($achievement, ['note' => 'Great job!']);

    $link = BabyAchievement::query()->first();

    $this->getJson('/api/v1/baby-achievements')
        ->assertOk()
        ->assertJsonPath('data.0.id', $link->uuid)
        ->assertJsonPath('data.0.achievement_id', $achievement->uuid)
        ->assertJsonPath('data.0.note', 'Great job!')
        ->assertJsonPath('data.0.created_at', $link->created_at->toIso8601ZuluString());
});
```

- [ ] **Step 2: Run test to verify it fails**

Run: `vendor/bin/sail artisan test --compact --filter=BabyAchievementTest`
Expected: FAIL

- [ ] **Step 3: Update BabyAchievementResource**

```php
/** @mixin BabyAchievement */
final class BabyAchievementResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->uuid,
            'achievement_id' => $this->achievement->uuid,
            'note' => $this->note,
            'created_at' => $this->created_at->toIso8601ZuluString(),
        ];
    }
}
```

- [ ] **Step 4: Create ListBabyAchievements action**

```php
<?php

declare(strict_types=1);

namespace App\Actions\BabyAchievement;

use App\Models\Baby;
use App\Models\BabyAchievement;
use App\Models\Category;
use Illuminate\Database\Eloquent\Collection;

final readonly class ListBabyAchievements
{
    /**
     * @return Collection<int, BabyAchievement>
     */
    public function handle(Baby $baby, ?Category $category = null): Collection
    {
        return BabyAchievement::query()
            ->where('baby_id', $baby->id)
            ->when($category, fn ($query) => $query->whereHas(
                'achievement',
                fn ($q) => $q->where('category_id', $category->id),
            ))
            ->with('achievement')
            ->orderBy('id')
            ->get();
    }
}
```

- [ ] **Step 5: Create BabyAchievementController with index**

```php
<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Actions\BabyAchievement\ListBabyAchievements;
use App\Http\Resources\BabyAchievementResource;
use App\Models\Baby;
use App\Models\Category;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

final readonly class BabyAchievementController
{
    public function index(Request $request, Baby $baby, ListBabyAchievements $action): AnonymousResourceCollection
    {
        $category = null;

        if ($request->has('category')) {
            $request->validate(['category' => ['required', 'uuid']]);
            $category = Category::query()->where('uuid', $request->input('category'))->firstOrFail();
        }

        return BabyAchievementResource::collection($action->handle($baby, $category));
    }
}
```

- [ ] **Step 6: Add route**

In `routes/api/v1.php`:

```php
Route::apiResource('baby-achievements', BabyAchievementController::class)->except(['show']);
```

- [ ] **Step 7: Run test to verify it passes**

Run: `vendor/bin/sail artisan test --compact --filter=BabyAchievementTest`
Expected: PASS

- [ ] **Step 8: Run Pint**

Run: `vendor/bin/sail bin pint --dirty --format agent`

- [ ] **Step 9: Commit**

```
feat(achievements): add GET /v1/baby-achievements endpoint with category filter
```

### Task 8: POST /v1/baby-achievements — store + tests

**Files:**
- Create: `app/Http/Requests/Api/V1/StoreBabyAchievementRequest.php`
- Create: `app/Actions/BabyAchievement/CreateBabyAchievement.php`
- Modify: `app/Http/Controllers/Api/V1/BabyAchievementController.php`
- Modify: `routes/api/v1.php`
- Modify: `tests/Feature/Api/V1/BabyAchievementTest.php`

- [ ] **Step 1: Add tests to BabyAchievementTest.php**

```php
it('creates a baby achievement', function (): void {
    $achievement = Achievement::factory()->for($this->category)->create();

    $this->postJson('/api/v1/baby-achievements', [
        'achievement_id' => $achievement->uuid,
        'note' => 'During tummy time!',
    ])
        ->assertCreated()
        ->assertJsonPath('data.achievement_id', $achievement->uuid)
        ->assertJsonPath('data.note', 'During tummy time!');

    $this->assertDatabaseHas('baby_achievement', [
        'baby_id' => $this->baby->id,
        'achievement_id' => $achievement->id,
        'note' => 'During tummy time!',
    ]);
});

it('creates a baby achievement without note', function (): void {
    $achievement = Achievement::factory()->for($this->category)->create();

    $this->postJson('/api/v1/baby-achievements', [
        'achievement_id' => $achievement->uuid,
    ])
        ->assertCreated()
        ->assertJsonPath('data.note', null);
});

it('creates a baby achievement with provided uuid', function (): void {
    $achievement = Achievement::factory()->for($this->category)->create();
    $uuid = fake()->uuid();

    $this->postJson('/api/v1/baby-achievements', [
        'uuid' => $uuid,
        'achievement_id' => $achievement->uuid,
    ])
        ->assertCreated()
        ->assertJsonPath('data.id', $uuid);
});

it('prevents duplicate baby achievements for the same achievement', function (): void {
    $achievement = Achievement::factory()->for($this->category)->create();
    $this->baby->achievements()->attach($achievement, ['note' => null]);

    $this->postJson('/api/v1/baby-achievements', [
        'achievement_id' => $achievement->uuid,
    ])
        ->assertUnprocessable();
});

it('validates required fields for creating baby achievement', function (): void {
    $this->postJson('/api/v1/baby-achievements', [])
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['achievement_id']);
});

it('validates achievement_id exists', function (): void {
    $this->postJson('/api/v1/baby-achievements', [
        'achievement_id' => fake()->uuid(),
    ])
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['achievement_id']);
});
```

- [ ] **Step 2: Run test to verify new tests fail**

Run: `vendor/bin/sail artisan test --compact --filter=BabyAchievementTest`
Expected: New tests FAIL

- [ ] **Step 3: Create StoreBabyAchievementRequest**

```php
<?php

declare(strict_types=1);

namespace App\Http\Requests\Api\V1;

use Illuminate\Foundation\Http\FormRequest;

final class StoreBabyAchievementRequest extends FormRequest
{
    /**
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        return [
            'uuid' => ['sometimes', 'uuid', 'unique:baby_achievement,uuid'],
            'achievement_id' => ['required', 'uuid', 'exists:achievements,uuid'],
            'note' => ['nullable', 'string'],
        ];
    }
}
```

- [ ] **Step 4: Create CreateBabyAchievement action**

```php
<?php

declare(strict_types=1);

namespace App\Actions\BabyAchievement;

use App\Models\Achievement;
use App\Models\Baby;
use App\Models\BabyAchievement;
use Illuminate\Support\Str;

final readonly class CreateBabyAchievement
{
    public function handle(Baby $baby, Achievement $achievement, ?string $note = null, ?string $uuid = null): BabyAchievement
    {
        $baby->achievements()->attach($achievement, [
            'uuid' => $uuid ?? (string) Str::uuid(),
            'note' => $note,
        ]);

        /** @var BabyAchievement */
        return BabyAchievement::query()
            ->with('achievement')
            ->where('baby_id', $baby->id)
            ->where('achievement_id', $achievement->id)
            ->firstOrFail();
    }
}
```

- [ ] **Step 5: Add store method to BabyAchievementController**

Add required imports to the controller:
```php
use App\Actions\BabyAchievement\CreateBabyAchievement;
use App\Http\Requests\Api\V1\StoreBabyAchievementRequest;
use App\Models\Achievement;
use App\Models\BabyAchievement;
use Illuminate\Http\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
```

```php
public function store(StoreBabyAchievementRequest $request, Baby $baby, CreateBabyAchievement $action): JsonResponse
{
    $achievement = Achievement::query()
        ->where('uuid', $request->validated('achievement_id'))
        ->firstOrFail();

    $existing = BabyAchievement::query()
        ->where('baby_id', $baby->id)
        ->where('achievement_id', $achievement->id)
        ->exists();

    if ($existing) {
        abort(Response::HTTP_UNPROCESSABLE_ENTITY, 'Achievement already linked to this baby.');
    }

    $link = $action->handle(
        baby: $baby,
        achievement: $achievement,
        note: $request->validated('note'),
        uuid: $request->validated('uuid'),
    );

    return BabyAchievementResource::make($link)
        ->response()
        ->setStatusCode(Response::HTTP_CREATED);
}
```

- [ ] **Step 6: Run test to verify it passes**

Run: `vendor/bin/sail artisan test --compact --filter=BabyAchievementTest`
Expected: PASS

- [ ] **Step 8: Run Pint**

Run: `vendor/bin/sail bin pint --dirty --format agent`

- [ ] **Step 9: Commit**

```
feat(achievements): add POST /v1/baby-achievements endpoint
```

### Task 9: PUT /v1/baby-achievements/{babyAchievement} — update + tests

**Files:**
- Create: `app/Http/Requests/Api/V1/UpdateBabyAchievementRequest.php`
- Create: `app/Actions/BabyAchievement/UpdateBabyAchievement.php`
- Modify: `app/Http/Controllers/Api/V1/BabyAchievementController.php`
- Modify: `routes/api/v1.php`
- Modify: `tests/Feature/Api/V1/BabyAchievementTest.php`

- [ ] **Step 1: Add tests**

```php
it('updates a baby achievement note', function (): void {
    $achievement = Achievement::factory()->for($this->category)->create();
    $this->baby->achievements()->attach($achievement, ['note' => 'Old note']);
    $link = BabyAchievement::query()->first();

    $this->putJson("/api/v1/baby-achievements/{$link->uuid}", [
        'note' => 'Updated note',
    ])
        ->assertOk()
        ->assertJsonPath('data.note', 'Updated note');

    $this->assertDatabaseHas('baby_achievement', [
        'id' => $link->id,
        'note' => 'Updated note',
    ]);
});

it('clears a baby achievement note', function (): void {
    $achievement = Achievement::factory()->for($this->category)->create();
    $this->baby->achievements()->attach($achievement, ['note' => 'Has note']);
    $link = BabyAchievement::query()->first();

    $this->putJson("/api/v1/baby-achievements/{$link->uuid}", [
        'note' => null,
    ])
        ->assertOk()
        ->assertJsonPath('data.note', null);
});
```

- [ ] **Step 2: Run test to verify new tests fail**

Run: `vendor/bin/sail artisan test --compact --filter=BabyAchievementTest`
Expected: New tests FAIL

- [ ] **Step 3: Create UpdateBabyAchievementRequest**

```php
<?php

declare(strict_types=1);

namespace App\Http\Requests\Api\V1;

use Illuminate\Foundation\Http\FormRequest;

final class UpdateBabyAchievementRequest extends FormRequest
{
    /**
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        return [
            'note' => ['nullable', 'string'],
        ];
    }
}
```

- [ ] **Step 4: Create UpdateBabyAchievement action**

```php
<?php

declare(strict_types=1);

namespace App\Actions\BabyAchievement;

use App\Models\BabyAchievement;

final readonly class UpdateBabyAchievement
{
    /**
     * @param array<string, mixed> $data
     */
    public function handle(BabyAchievement $babyAchievement, array $data): BabyAchievement
    {
        $babyAchievement->update($data);

        return $babyAchievement->refresh();
    }
}
```

- [ ] **Step 5: Add update method to BabyAchievementController**

Add required imports:
```php
use App\Actions\BabyAchievement\UpdateBabyAchievement;
use App\Http\Requests\Api\V1\UpdateBabyAchievementRequest;
```

```php
public function update(
    UpdateBabyAchievementRequest $request,
    BabyAchievement $babyAchievement,
    UpdateBabyAchievement $action,
): BabyAchievementResource {
    $action->handle($babyAchievement, $request->validated());

    return BabyAchievementResource::make($babyAchievement->load('achievement'));
}
```

- [ ] **Step 6: Run test to verify it passes**

Run: `vendor/bin/sail artisan test --compact --filter=BabyAchievementTest`
Expected: PASS

- [ ] **Step 7: Run Pint**

Run: `vendor/bin/sail bin pint --dirty --format agent`

- [ ] **Step 8: Commit**

```
feat(achievements): add PUT /v1/baby-achievements/{babyAchievement} endpoint
```

### Task 10: DELETE /v1/baby-achievements/{babyAchievement} — destroy + tests

**Files:**
- Create: `app/Actions/BabyAchievement/DeleteBabyAchievement.php`
- Modify: `app/Http/Controllers/Api/V1/BabyAchievementController.php`
- Modify: `routes/api/v1.php`
- Modify: `tests/Feature/Api/V1/BabyAchievementTest.php`

- [ ] **Step 1: Add tests**

```php
it('deletes a baby achievement', function (): void {
    $achievement = Achievement::factory()->for($this->category)->create();
    $this->baby->achievements()->attach($achievement, ['note' => null]);
    $link = BabyAchievement::query()->first();

    $this->deleteJson("/api/v1/baby-achievements/{$link->uuid}")
        ->assertNoContent();

    $this->assertDatabaseMissing('baby_achievement', ['id' => $link->id]);
});

it('returns 404 when deleting non-existent baby achievement', function (): void {
    $this->deleteJson('/api/v1/baby-achievements/' . fake()->uuid())
        ->assertNotFound();
});
```

- [ ] **Step 2: Run test to verify new tests fail**

Run: `vendor/bin/sail artisan test --compact --filter=BabyAchievementTest`
Expected: New tests FAIL

- [ ] **Step 3: Create DeleteBabyAchievement action**

```php
<?php

declare(strict_types=1);

namespace App\Actions\BabyAchievement;

use App\Models\BabyAchievement;

final readonly class DeleteBabyAchievement
{
    public function handle(BabyAchievement $babyAchievement): void
    {
        $babyAchievement->delete();
    }
}
```

- [ ] **Step 4: Add destroy method to BabyAchievementController**

Add required import:
```php
use App\Actions\BabyAchievement\DeleteBabyAchievement;
```

```php
public function destroy(BabyAchievement $babyAchievement, DeleteBabyAchievement $action): Response
{
    $action->handle($babyAchievement);

    return response()->noContent();
}
```

- [ ] **Step 5: Run test to verify it passes**

Run: `vendor/bin/sail artisan test --compact --filter=BabyAchievementTest`
Expected: PASS

- [ ] **Step 6: Run Pint**

Run: `vendor/bin/sail bin pint --dirty --format agent`

- [ ] **Step 7: Commit**

```
feat(achievements): add DELETE /v1/baby-achievements/{babyAchievement} endpoint
```

---

## Chunk 5: Final Verification

### Task 11: Run full test suite and cleanup

- [ ] **Step 1: Run all tests**

Run: `vendor/bin/sail artisan test --compact`
Expected: All tests pass, no references to deleted code.

- [ ] **Step 2: Run Pint on all files**

Run: `vendor/bin/sail bin pint --dirty --format agent`

- [ ] **Step 3: Verify no dead imports or references**

Check that no file imports deleted classes (`CreateAchievement`, `DeleteAchievement`, `LinkAchievement`, etc.).

- [ ] **Step 4: Verify routes**

Run: `vendor/bin/sail artisan route:list --path=api/v1`

Expected achievement-related routes (plus existing milk/health/user routes):
```
GET    /api/v1/categories                            api.v1.categories.index
GET    /api/v1/achievements                          api.v1.achievements.index
GET    /api/v1/baby-achievements                     baby-achievements.index
POST   /api/v1/baby-achievements                     baby-achievements.store
PUT    /api/v1/baby-achievements/{baby_achievement}  baby-achievements.update
DELETE /api/v1/baby-achievements/{baby_achievement}  baby-achievements.destroy
```

- [ ] **Step 5: Final commit if any cleanup was needed**
