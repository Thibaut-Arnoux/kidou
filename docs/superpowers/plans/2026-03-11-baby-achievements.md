# Baby Achievements Implementation Plan

> **For agentic workers:** REQUIRED: Use superpowers:subagent-driven-development (if subagents available) or superpowers:executing-plans to implement this plan. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Build a gamification feature that tracks developmental milestones for babies, with predefined and custom achievements organized by category, and linking/unlinking to babies with timestamps and notes.

**Architecture:** Three-layer API (Controller → Action → Model) following existing codebase patterns. UUID-based routing with observers for auto-generation. Pivot model for baby-achievement links with progress tracking per category.

**Tech Stack:** Laravel 12, Pest 4, Eloquent ORM, API Resources, Form Requests, Laravel Sail

**Spec:** `docs/superpowers/specs/2026-03-11-baby-achievements-design.md`

---

## Chunk 1: Database, Models & Seeders

### Task 1: Create Migrations

**Files:**
- Create: `database/migrations/xxxx_create_categories_table.php`
- Create: `database/migrations/xxxx_create_achievements_table.php`
- Create: `database/migrations/xxxx_create_baby_achievement_table.php`

- [ ] **Step 1: Generate the three migrations**

```bash
vendor/bin/sail artisan make:migration create_categories_table --no-interaction
vendor/bin/sail artisan make:migration create_achievements_table --no-interaction
vendor/bin/sail artisan make:migration create_baby_achievement_table --no-interaction
```

- [ ] **Step 2: Implement categories migration**

Edit the generated `create_categories_table` migration:

```php
Schema::create('categories', function (Blueprint $table): void {
    $table->id();
    $table->uuid('uuid')->unique();
    $table->string('name');
    $table->string('slug')->unique();
    $table->boolean('is_custom')->default(false);
    $table->timestamps();
});
```

- [ ] **Step 3: Implement achievements migration**

Edit the generated `create_achievements_table` migration:

```php
Schema::create('achievements', function (Blueprint $table): void {
    $table->id();
    $table->uuid('uuid')->unique();
    $table->foreignId('category_id')->constrained()->cascadeOnDelete();
    $table->foreignId('user_id')->nullable()->constrained()->cascadeOnDelete();
    $table->string('name');
    $table->text('description')->nullable();
    $table->unsignedInteger('expected_age_min_months')->nullable();
    $table->unsignedInteger('expected_age_max_months')->nullable();
    $table->timestamps();
});
```

- [ ] **Step 4: Implement baby_achievement pivot migration**

Edit the generated `create_baby_achievement_table` migration:

```php
Schema::create('baby_achievement', function (Blueprint $table): void {
    $table->id();
    $table->uuid('uuid')->unique();
    $table->foreignId('baby_id')->constrained()->cascadeOnDelete();
    $table->foreignId('achievement_id')->constrained()->cascadeOnDelete();
    $table->dateTime('achieved_at');
    $table->text('note')->nullable();
    $table->timestamps();

    $table->unique(['baby_id', 'achievement_id']);
});
```

- [ ] **Step 5: Run migrations**

```bash
vendor/bin/sail artisan migrate
```

Expected: All three tables created successfully.

- [ ] **Step 6: Commit**

```bash
git add database/migrations/*_create_categories_table.php database/migrations/*_create_achievements_table.php database/migrations/*_create_baby_achievement_table.php
git commit -m "feat(achievements): add migrations for categories, achievements, and baby_achievement tables"
```

---

### Task 2: Create Observers

**Files:**
- Create: `app/Observers/CategoryObserver.php`
- Create: `app/Observers/AchievementObserver.php`
- Create: `app/Observers/BabyAchievementObserver.php`

- [ ] **Step 1: Create CategoryObserver**

```bash
vendor/bin/sail artisan make:observer CategoryObserver --model=Category --no-interaction
```

Edit to match existing pattern (see `app/Observers/BabyObserver.php`):

```php
<?php

declare(strict_types=1);

namespace App\Observers;

use App\Models\Category;
use Illuminate\Support\Str;

final class CategoryObserver
{
    public function creating(Category $category): void
    {
        if (empty($category->uuid)) {
            $category->uuid = (string) Str::uuid();
        }
    }
}
```

- [ ] **Step 2: Create AchievementObserver**

```bash
vendor/bin/sail artisan make:observer AchievementObserver --model=Achievement --no-interaction
```

Edit to same pattern:

```php
<?php

declare(strict_types=1);

namespace App\Observers;

use App\Models\Achievement;
use Illuminate\Support\Str;

final class AchievementObserver
{
    public function creating(Achievement $achievement): void
    {
        if (empty($achievement->uuid)) {
            $achievement->uuid = (string) Str::uuid();
        }
    }
}
```

- [ ] **Step 3: Create BabyAchievementObserver**

```bash
vendor/bin/sail artisan make:observer BabyAchievementObserver --no-interaction
```

Edit to same pattern:

```php
<?php

declare(strict_types=1);

namespace App\Observers;

use App\Models\BabyAchievement;
use Illuminate\Support\Str;

final class BabyAchievementObserver
{
    public function creating(BabyAchievement $babyAchievement): void
    {
        if (empty($babyAchievement->uuid)) {
            $babyAchievement->uuid = (string) Str::uuid();
        }
    }
}
```

- [ ] **Step 4: Commit**

```bash
git add app/Observers/CategoryObserver.php app/Observers/AchievementObserver.php app/Observers/BabyAchievementObserver.php
git commit -m "feat(achievements): add observers for UUID auto-generation"
```

---

### Task 3: Create Models

**Files:**
- Create: `app/Models/Category.php`
- Create: `app/Models/Achievement.php`
- Create: `app/Models/BabyAchievement.php`
- Modify: `app/Models/Baby.php` (add `achievements()` relationship)

- [ ] **Step 1: Create Category model**

```bash
vendor/bin/sail artisan make:model Category --no-interaction
```

Edit to:

```php
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
 * @property bool $is_custom
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
        'is_custom',
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
            'is_custom' => 'boolean',
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

- [ ] **Step 2: Create Achievement model**

```bash
vendor/bin/sail artisan make:model Achievement --no-interaction
```

Edit to:

```php
<?php

declare(strict_types=1);

namespace App\Models;

use App\Observers\AchievementObserver;
use Carbon\CarbonInterface;
use Database\Factories\AchievementFactory;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

/**
 * @property-read int $id
 * @property string $uuid
 * @property-read int $category_id
 * @property-read int|null $user_id
 * @property string $name
 * @property string|null $description
 * @property int|null $expected_age_min_months
 * @property int|null $expected_age_max_months
 * @property-read CarbonInterface $created_at
 * @property-read CarbonInterface $updated_at
 * @property-read Category $category
 * @property-read User|null $user
 * @property-read BabyAchievement|null $babyLink
 */
#[ObservedBy(AchievementObserver::class)]
final class Achievement extends Model
{
    /** @use HasFactory<AchievementFactory> */
    use HasFactory;

    /** @var list<string> */
    protected $fillable = [
        'category_id',
        'user_id',
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
            'user_id' => 'integer',
            'name' => 'string',
            'description' => 'string',
            'expected_age_min_months' => 'integer',
            'expected_age_max_months' => 'integer',
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
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * @return BelongsToMany<Baby, $this>
     */
    public function babies(): BelongsToMany
    {
        return $this->belongsToMany(Baby::class)
            ->using(BabyAchievement::class)
            ->as('link')
            ->withPivot('uuid', 'achieved_at', 'note')
            ->withTimestamps();
    }

    /**
     * @param Builder<Achievement> $query
     */
    #[Scope]
    protected function predefined(Builder $query): void
    {
        $query->whereNull('user_id');
    }

    /**
     * @param Builder<Achievement> $query
     */
    #[Scope]
    protected function customForUser(Builder $query, User $user): void
    {
        $query->where('user_id', $user->id);
    }
}
```

- [ ] **Step 3: Create BabyAchievement pivot model**

Create `app/Models/BabyAchievement.php` manually (artisan make:model would create a regular model):

```php
<?php

declare(strict_types=1);

namespace App\Models;

use App\Observers\BabyAchievementObserver;
use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;
use Illuminate\Database\Eloquent\Relations\Pivot;

/**
 * @property-read int $id
 * @property string $uuid
 * @property-read int $baby_id
 * @property-read int $achievement_id
 * @property CarbonInterface $achieved_at
 * @property string|null $note
 * @property-read CarbonInterface $created_at
 * @property-read CarbonInterface $updated_at
 */
#[ObservedBy(BabyAchievementObserver::class)]
final class BabyAchievement extends Pivot
{
    protected $table = 'baby_achievement';

    public $incrementing = true;

    /** @var list<string> */
    protected $fillable = [
        'baby_id',
        'achievement_id',
        'achieved_at',
        'note',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'baby_id' => 'integer',
            'achievement_id' => 'integer',
            'achieved_at' => 'datetime',
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
        ];
    }
}
```

- [ ] **Step 4: Add achievements relationship to Baby model**

Add to `app/Models/Baby.php` — new import and relationship method:

```php
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
```

Add to the PHPDoc block:

```php
 * @property-read Collection<int, Achievement> $achievements
```

Add method:

```php
/**
 * @return BelongsToMany<Achievement, $this>
 */
public function achievements(): BelongsToMany
{
    return $this->belongsToMany(Achievement::class)
        ->using(BabyAchievement::class)
        ->as('link')
        ->withPivot('uuid', 'achieved_at', 'note')
        ->withTimestamps();
}
```

- [ ] **Step 5: Verify models load**

```bash
vendor/bin/sail artisan tinker --execute="new App\Models\Category; new App\Models\Achievement; new App\Models\BabyAchievement; echo 'OK';"
```

Expected: `OK`

- [ ] **Step 6: Commit**

```bash
git add app/Models/Category.php app/Models/Achievement.php app/Models/BabyAchievement.php app/Models/Baby.php
git commit -m "feat(achievements): add Category, Achievement, BabyAchievement models and Baby relationship"
```

---

### Task 4: Create Factories

**Files:**
- Create: `database/factories/CategoryFactory.php`
- Create: `database/factories/AchievementFactory.php`

- [ ] **Step 1: Create CategoryFactory**

```bash
vendor/bin/sail artisan make:factory CategoryFactory --model=Category --no-interaction
```

Edit to:

```php
<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Category;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<Category> */
final class CategoryFactory extends Factory
{
    /** @return array<string, mixed> */
    public function definition(): array
    {
        $name = fake()->unique()->words(2, true);

        return [
            'name' => $name,
            'slug' => str($name)->slug()->toString(),
            'is_custom' => false,
        ];
    }

    public function custom(): self
    {
        return $this->state(fn (array $attributes): array => [
            'name' => 'Custom',
            'slug' => 'custom',
            'is_custom' => true,
        ]);
    }
}
```

- [ ] **Step 2: Create AchievementFactory**

```bash
vendor/bin/sail artisan make:factory AchievementFactory --model=Achievement --no-interaction
```

Edit to:

```php
<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Achievement;
use App\Models\Category;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<Achievement> */
final class AchievementFactory extends Factory
{
    /** @return array<string, mixed> */
    public function definition(): array
    {
        $minAge = fake()->numberBetween(0, 30);

        return [
            'category_id' => Category::factory(),
            'user_id' => null,
            'name' => fake()->sentence(3),
            'description' => fake()->sentence(),
            'expected_age_min_months' => $minAge,
            'expected_age_max_months' => fake()->numberBetween($minAge, 36),
        ];
    }

    public function customFor(User $user): self
    {
        return $this->state(fn (array $attributes): array => [
            'user_id' => $user->id,
            'category_id' => Category::factory()->custom(),
        ]);
    }
}
```

- [ ] **Step 3: Verify factories work**

```bash
vendor/bin/sail artisan tinker --execute="App\Models\Category::factory()->make(); App\Models\Achievement::factory()->make(); echo 'OK';"
```

Expected: `OK`

- [ ] **Step 4: Commit**

```bash
git add database/factories/CategoryFactory.php database/factories/AchievementFactory.php
git commit -m "feat(achievements): add Category and Achievement factories"
```

---

### Task 5: Create Seeders

**Files:**
- Create: `database/seeders/CategorySeeder.php`
- Create: `database/seeders/AchievementSeeder.php`

- [ ] **Step 1: Create CategorySeeder**

```bash
vendor/bin/sail artisan make:seeder CategorySeeder --no-interaction
```

Edit to:

```php
<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\Category;
use Illuminate\Database\Seeder;

final class CategorySeeder extends Seeder
{
    public function run(): void
    {
        $categories = [
            ['name' => 'Motor Skills', 'slug' => 'motor-skills', 'is_custom' => false],
            ['name' => 'Language', 'slug' => 'language', 'is_custom' => false],
            ['name' => 'Social & Emotional', 'slug' => 'social-emotional', 'is_custom' => false],
            ['name' => 'Cognitive', 'slug' => 'cognitive', 'is_custom' => false],
            ['name' => 'Self-Care', 'slug' => 'self-care', 'is_custom' => false],
            ['name' => 'Custom', 'slug' => 'custom', 'is_custom' => true],
        ];

        foreach ($categories as $category) {
            Category::query()->firstOrCreate(
                ['slug' => $category['slug']],
                $category,
            );
        }
    }
}
```

- [ ] **Step 2: Create AchievementSeeder**

```bash
vendor/bin/sail artisan make:seeder AchievementSeeder --no-interaction
```

Edit to seed all 56 predefined achievements. The seeder calls `CategorySeeder` first, then creates achievements grouped by category slug. Uses `firstOrCreate` keyed on `name` + `category_id` to be idempotent.

```php
<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\Achievement;
use App\Models\Category;
use Illuminate\Database\Seeder;

final class AchievementSeeder extends Seeder
{
    public function run(): void
    {
        $this->call(CategorySeeder::class);

        $achievements = [
            'motor-skills' => [
                ['name' => 'Head Control', 'description' => 'Holds head steady and upright when held in a sitting position.', 'expected_age_min_months' => 1, 'expected_age_max_months' => 4],
                ['name' => 'Pushes Up on Arms', 'description' => 'Lifts head and chest off the floor while lying on tummy.', 'expected_age_min_months' => 2, 'expected_age_max_months' => 4],
                ['name' => 'Rolls Over', 'description' => 'Rolls from tummy to back and back to tummy.', 'expected_age_min_months' => 3, 'expected_age_max_months' => 6],
                ['name' => 'Sits Without Support', 'description' => 'Sits steadily on their own without needing to prop on hands.', 'expected_age_min_months' => 5, 'expected_age_max_months' => 8],
                ['name' => 'Raking Grasp', 'description' => 'Uses fingers to rake small objects toward themselves.', 'expected_age_min_months' => 5, 'expected_age_max_months' => 8],
                ['name' => 'Crawls', 'description' => 'Moves forward on hands and knees in a coordinated way.', 'expected_age_min_months' => 6, 'expected_age_max_months' => 10],
                ['name' => 'Pulls to Stand', 'description' => 'Pulls themselves up to a standing position using furniture.', 'expected_age_min_months' => 7, 'expected_age_max_months' => 12],
                ['name' => 'Pincer Grasp', 'description' => 'Picks up small objects between thumb and forefinger.', 'expected_age_min_months' => 8, 'expected_age_max_months' => 12],
                ['name' => 'Cruises Along Furniture', 'description' => 'Walks sideways while holding onto furniture for support.', 'expected_age_min_months' => 8, 'expected_age_max_months' => 13],
                ['name' => 'First Steps Independently', 'description' => 'Takes several steps without holding onto anything.', 'expected_age_min_months' => 9, 'expected_age_max_months' => 15],
                ['name' => 'Stacks Two Blocks', 'description' => 'Places one block on top of another deliberately.', 'expected_age_min_months' => 11, 'expected_age_max_months' => 16],
                ['name' => 'Walks Steadily', 'description' => 'Walks with a stable, coordinated gait and rarely falls.', 'expected_age_min_months' => 12, 'expected_age_max_months' => 18],
                ['name' => 'Kicks a Ball', 'description' => 'Kicks a ball forward with one foot while standing.', 'expected_age_min_months' => 18, 'expected_age_max_months' => 24],
                ['name' => 'Runs', 'description' => 'Runs with a fairly coordinated gait.', 'expected_age_min_months' => 18, 'expected_age_max_months' => 26],
                ['name' => 'Climbs Stairs with Help', 'description' => 'Walks up stairs while holding a railing or an adult\'s hand.', 'expected_age_min_months' => 18, 'expected_age_max_months' => 26],
                ['name' => 'Jumps with Both Feet', 'description' => 'Jumps off the ground with both feet leaving the floor.', 'expected_age_min_months' => 24, 'expected_age_max_months' => 36],
                ['name' => 'Pedals a Tricycle', 'description' => 'Pushes pedals on a tricycle to move forward.', 'expected_age_min_months' => 28, 'expected_age_max_months' => 36],
            ],
            'language' => [
                ['name' => 'Cooing', 'description' => 'Produces soft vowel-like sounds in response to voices.', 'expected_age_min_months' => 1, 'expected_age_max_months' => 4],
                ['name' => 'Babbling', 'description' => 'Repeats consonant-vowel combinations like "bababa" or "mamama".', 'expected_age_min_months' => 4, 'expected_age_max_months' => 8],
                ['name' => 'Responds to Own Name', 'description' => 'Turns head or looks toward the speaker when their name is called.', 'expected_age_min_months' => 5, 'expected_age_max_months' => 9],
                ['name' => 'First Word', 'description' => 'Says one or two recognizable words with meaning.', 'expected_age_min_months' => 9, 'expected_age_max_months' => 14],
                ['name' => 'Points to Show or Request', 'description' => 'Points at objects to express interest or ask for something.', 'expected_age_min_months' => 9, 'expected_age_max_months' => 14],
                ['name' => 'Follows Simple Instructions', 'description' => 'Understands and carries out a one-step request.', 'expected_age_min_months' => 10, 'expected_age_max_months' => 16],
                ['name' => 'Uses 10+ Words', 'description' => 'Has a spoken vocabulary of at least ten distinct words.', 'expected_age_min_months' => 14, 'expected_age_max_months' => 20],
                ['name' => 'Two-Word Phrases', 'description' => 'Combines two words to form simple phrases like "more milk".', 'expected_age_min_months' => 18, 'expected_age_max_months' => 24],
                ['name' => 'Names Familiar Objects', 'description' => 'Can label common objects when asked "what\'s this?"', 'expected_age_min_months' => 18, 'expected_age_max_months' => 26],
                ['name' => 'Uses Short Sentences', 'description' => 'Speaks in sentences of three or more words.', 'expected_age_min_months' => 24, 'expected_age_max_months' => 36],
                ['name' => 'Strangers Can Understand Speech', 'description' => 'Most of what the child says can be understood by unfamiliar adults.', 'expected_age_min_months' => 30, 'expected_age_max_months' => 36],
            ],
            'social-emotional' => [
                ['name' => 'Social Smile', 'description' => 'Smiles in response to a caregiver\'s face or voice.', 'expected_age_min_months' => 1, 'expected_age_max_months' => 3],
                ['name' => 'Enjoys Social Play', 'description' => 'Laughs during peek-a-boo and other simple interactive games.', 'expected_age_min_months' => 3, 'expected_age_max_months' => 6],
                ['name' => 'Stranger Anxiety', 'description' => 'Shows wariness or distress around unfamiliar people.', 'expected_age_min_months' => 6, 'expected_age_max_months' => 10],
                ['name' => 'Separation Anxiety', 'description' => 'Becomes upset when a primary caregiver leaves the room.', 'expected_age_min_months' => 7, 'expected_age_max_months' => 12],
                ['name' => 'Waves Bye-Bye', 'description' => 'Waves hand in a social gesture when someone leaves.', 'expected_age_min_months' => 8, 'expected_age_max_months' => 14],
                ['name' => 'Shows Affection', 'description' => 'Hugs, kisses, or cuddles with familiar people spontaneously.', 'expected_age_min_months' => 12, 'expected_age_max_months' => 18],
                ['name' => 'Parallel Play', 'description' => 'Plays alongside other children doing similar activities.', 'expected_age_min_months' => 18, 'expected_age_max_months' => 26],
                ['name' => 'Shows Empathy', 'description' => 'Notices when another person is upset and may try to comfort them.', 'expected_age_min_months' => 18, 'expected_age_max_months' => 28],
                ['name' => 'Takes Turns in Simple Games', 'description' => 'Can wait briefly and alternate turns during a simple game.', 'expected_age_min_months' => 24, 'expected_age_max_months' => 36],
                ['name' => 'Engages in Pretend Play with Others', 'description' => 'Participates in make-believe scenarios with peers or caregivers.', 'expected_age_min_months' => 28, 'expected_age_max_months' => 36],
            ],
            'cognitive' => [
                ['name' => 'Follows Moving Objects', 'description' => 'Tracks a slowly moving object with their eyes.', 'expected_age_min_months' => 0, 'expected_age_max_months' => 3],
                ['name' => 'Explores Objects with Hands and Mouth', 'description' => 'Brings objects to mouth and manipulates them.', 'expected_age_min_months' => 3, 'expected_age_max_months' => 6],
                ['name' => 'Object Permanence', 'description' => 'Understands that an object still exists even when hidden.', 'expected_age_min_months' => 6, 'expected_age_max_months' => 10],
                ['name' => 'Cause and Effect', 'description' => 'Intentionally repeats actions to see results.', 'expected_age_min_months' => 6, 'expected_age_max_months' => 12],
                ['name' => 'Imitates Actions', 'description' => 'Copies simple gestures and actions performed by adults.', 'expected_age_min_months' => 8, 'expected_age_max_months' => 14],
                ['name' => 'Simple Shape Sorting', 'description' => 'Fits basic shapes into the correct holes on a shape sorter.', 'expected_age_min_months' => 18, 'expected_age_max_months' => 26],
                ['name' => 'Pretend Play', 'description' => 'Uses objects symbolically, such as pretending a block is a phone.', 'expected_age_min_months' => 18, 'expected_age_max_months' => 28],
                ['name' => 'Sorts by Color or Shape', 'description' => 'Groups objects together based on one attribute.', 'expected_age_min_months' => 28, 'expected_age_max_months' => 36],
            ],
            'self-care' => [
                ['name' => 'Holds Own Bottle', 'description' => 'Grasps and holds a bottle to feed independently.', 'expected_age_min_months' => 5, 'expected_age_max_months' => 9],
                ['name' => 'Finger Feeds', 'description' => 'Picks up small pieces of food and brings them to mouth.', 'expected_age_min_months' => 7, 'expected_age_max_months' => 10],
                ['name' => 'Drinks from Cup with Help', 'description' => 'Takes sips from a cup held or guided by a caregiver.', 'expected_age_min_months' => 8, 'expected_age_max_months' => 14],
                ['name' => 'Uses a Spoon (with spilling)', 'description' => 'Attempts to scoop food with a spoon, though messily.', 'expected_age_min_months' => 12, 'expected_age_max_months' => 18],
                ['name' => 'Removes Simple Clothing', 'description' => 'Pulls off loose items like socks, shoes, or a hat.', 'expected_age_min_months' => 12, 'expected_age_max_months' => 20],
                ['name' => 'Drinks from Cup Independently', 'description' => 'Holds and drinks from a cup without significant spilling.', 'expected_age_min_months' => 16, 'expected_age_max_months' => 24],
                ['name' => 'Uses a Spoon Neatly', 'description' => 'Feeds themselves with a spoon with minimal spilling.', 'expected_age_min_months' => 20, 'expected_age_max_months' => 28],
                ['name' => 'Shows Interest in Toilet Training', 'description' => 'Tells caregiver about wet or dirty diaper, or wants to sit on the potty.', 'expected_age_min_months' => 18, 'expected_age_max_months' => 30],
                ['name' => 'Helps with Dressing', 'description' => 'Cooperates by pushing arms through sleeves or stepping into pants.', 'expected_age_min_months' => 20, 'expected_age_max_months' => 30],
                ['name' => 'Washes and Dries Hands with Help', 'description' => 'Participates in hand washing with guidance.', 'expected_age_min_months' => 24, 'expected_age_max_months' => 36],
            ],
        ];

        foreach ($achievements as $categorySlug => $categoryAchievements) {
            $category = Category::query()->where('slug', $categorySlug)->firstOrFail();

            foreach ($categoryAchievements as $achievement) {
                Achievement::query()->firstOrCreate(
                    ['name' => $achievement['name'], 'category_id' => $category->id],
                    [...$achievement, 'user_id' => null],
                );
            }
        }
    }
}
```

- [ ] **Step 3: Run seeders**

```bash
vendor/bin/sail artisan db:seed --class=AchievementSeeder
```

Expected: Seeds 6 categories and 56 achievements without errors.

- [ ] **Step 4: Verify seeded data**

```bash
vendor/bin/sail artisan tinker --execute="echo 'Categories: ' . App\Models\Category::count() . ', Achievements: ' . App\Models\Achievement::count();"
```

Expected: `Categories: 6, Achievements: 56`

- [ ] **Step 5: Commit**

```bash
git add database/seeders/CategorySeeder.php database/seeders/AchievementSeeder.php
git commit -m "feat(achievements): add category and achievement seeders with 56 predefined milestones"
```

---

## Chunk 2: Categories API

### Task 6: Categories — Resource, Action, Controller, Route, Tests

**Files:**
- Create: `app/Http/Resources/CategoryResource.php`
- Create: `app/Actions/Category/ListCategories.php`
- Create: `app/Http/Controllers/Api/V1/CategoryController.php`
- Modify: `routes/api/v1.php`
- Create: `tests/Feature/Api/V1/CategoryTest.php`

- [ ] **Step 1: Write the failing test**

```bash
vendor/bin/sail artisan make:test Api/V1/CategoryTest --pest --no-interaction
```

Edit `tests/Feature/Api/V1/CategoryTest.php`:

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

it('lists all categories with progress meta', function (): void {
    $category = Category::factory()->create();
    $achievements = Achievement::factory()->count(3)->for($category)->create();

    $this->baby->achievements()->attach($achievements->first(), [
        'achieved_at' => now(),
    ]);

    $this->getJson('/api/v1/categories')
        ->assertOk()
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.id', $category->uuid)
        ->assertJsonPath('data.0.name', $category->name)
        ->assertJsonPath('data.0.slug', $category->slug)
        ->assertJsonPath('data.0.is_custom', false)
        ->assertJsonPath('data.0.total_achievements', 3)
        ->assertJsonPath('data.0.completed_achievements', 1);
});

it('shows zero completed achievements when none are linked', function (): void {
    $category = Category::factory()->create();
    Achievement::factory()->count(2)->for($category)->create();

    $this->getJson('/api/v1/categories')
        ->assertOk()
        ->assertJsonPath('data.0.total_achievements', 2)
        ->assertJsonPath('data.0.completed_achievements', 0);
});

it('counts only predefined achievements for non-custom categories', function (): void {
    $category = Category::factory()->create();
    Achievement::factory()->count(2)->for($category)->create();
    // Custom achievement in a non-custom category should not be counted
    Achievement::factory()->for($category)->create(['user_id' => $this->user->id]);

    $this->getJson('/api/v1/categories')
        ->assertOk()
        ->assertJsonPath('data.0.total_achievements', 2);
});

it('counts only user custom achievements for the custom category', function (): void {
    $category = Category::factory()->custom()->create();
    $otherUser = User::factory()->create();

    Achievement::factory()->count(2)->for($category)->create(['user_id' => $this->user->id]);
    Achievement::factory()->for($category)->create(['user_id' => $otherUser->id]);

    $this->getJson('/api/v1/categories')
        ->assertOk()
        ->assertJsonPath('data.0.total_achievements', 2)
        ->assertJsonPath('data.0.completed_achievements', 0);
});
```

- [ ] **Step 2: Run tests to verify they fail**

```bash
vendor/bin/sail artisan test --compact --filter=CategoryTest
```

Expected: FAIL (route/controller not found).

- [ ] **Step 3: Create CategoryResource**

```bash
vendor/bin/sail artisan make:resource CategoryResource --no-interaction
```

Edit `app/Http/Resources/CategoryResource.php`:

```php
<?php

declare(strict_types=1);

namespace App\Http\Resources;

use App\Models\Category;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

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
            'is_custom' => $this->is_custom,
            'total_achievements' => $this->total_achievements,
            'completed_achievements' => $this->completed_achievements,
        ];
    }
}
```

- [ ] **Step 4: Create ListCategories action**

```bash
vendor/bin/sail artisan make:class Actions/Category/ListCategories --no-interaction
```

Edit `app/Actions/Category/ListCategories.php`.

The count logic must differentiate by category type:
- Non-custom categories: count only predefined achievements (`user_id IS NULL`)
- Custom category: count only achievements belonging to the current user

```php
<?php

declare(strict_types=1);

namespace App\Actions\Category;

use App\Models\Baby;
use App\Models\Category;
use App\Models\User;
use Illuminate\Database\Eloquent\Collection;

final readonly class ListCategories
{
    /**
     * @return Collection<int, Category>
     */
    public function handle(Baby $baby, User $user): Collection
    {
        return Category::query()
            ->withCount([
                'achievements as total_achievements' => function ($query) use ($user) {
                    /** @var \Illuminate\Database\Eloquent\Builder<\App\Models\Achievement> $query */
                    $query->where(function ($q) use ($user) {
                        $q->where(function ($inner) {
                            $inner->whereHas('category', fn ($c) => $c->where('is_custom', false))
                                ->whereNull('user_id');
                        })->orWhere(function ($inner) use ($user) {
                            $inner->whereHas('category', fn ($c) => $c->where('is_custom', true))
                                ->where('user_id', $user->id);
                        });
                    });
                },
                'achievements as completed_achievements' => function ($query) use ($baby, $user) {
                    /** @var \Illuminate\Database\Eloquent\Builder<\App\Models\Achievement> $query */
                    $query->where(function ($q) use ($user) {
                        $q->where(function ($inner) {
                            $inner->whereHas('category', fn ($c) => $c->where('is_custom', false))
                                ->whereNull('user_id');
                        })->orWhere(function ($inner) use ($user) {
                            $inner->whereHas('category', fn ($c) => $c->where('is_custom', true))
                                ->where('user_id', $user->id);
                        });
                    })->whereHas('babies', function ($q) use ($baby) {
                        $q->where('baby_achievement.baby_id', $baby->id);
                    });
                },
            ])
            ->orderBy('id')
            ->get();
    }
}
```

- [ ] **Step 5: Create CategoryController**

```bash
vendor/bin/sail artisan make:controller Api/V1/CategoryController --no-interaction
```

Edit `app/Http/Controllers/Api/V1/CategoryController.php`:

```php
<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Actions\Category\ListCategories;
use App\Http\Resources\CategoryResource;
use App\Models\Baby;
use App\Models\User;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

final readonly class CategoryController
{
    public function index(Baby $baby, ListCategories $action): AnonymousResourceCollection
    {
        /** @var User $user */
        $user = $baby->user;

        return CategoryResource::collection($action->handle($baby, $user));
    }
}
```

- [ ] **Step 6: Add route**

Add to `routes/api/v1.php` inside the `InjectDemoBaby` middleware group:

```php
use App\Http\Controllers\Api\V1\CategoryController;

Route::get('categories', [CategoryController::class, 'index']);
```

- [ ] **Step 7: Run tests to verify they pass**

```bash
vendor/bin/sail artisan test --compact --filter=CategoryTest
```

Expected: All 4 tests PASS.

- [ ] **Step 8: Run Pint**

```bash
vendor/bin/sail bin pint --dirty --format agent
```

- [ ] **Step 9: Commit**

```bash
git add app/Http/Resources/CategoryResource.php app/Actions/Category/ListCategories.php app/Http/Controllers/Api/V1/CategoryController.php routes/api/v1.php tests/Feature/Api/V1/CategoryTest.php
git commit -m "feat(achievements): add GET /v1/categories endpoint with progress meta"
```

---

## Chunk 3: Category Achievements API

### Task 7: Category Achievements — Resource, Action, Controller, Route, Tests

**Files:**
- Create: `app/Http/Resources/AchievementResource.php`
- Create: `app/Actions/CategoryAchievement/ListCategoryAchievements.php`
- Create: `app/Http/Controllers/Api/V1/CategoryAchievementController.php`
- Modify: `routes/api/v1.php`
- Create: `tests/Feature/Api/V1/CategoryAchievementTest.php`

- [ ] **Step 1: Write the failing test**

```bash
vendor/bin/sail artisan make:test Api/V1/CategoryAchievementTest --pest --no-interaction
```

Edit `tests/Feature/Api/V1/CategoryAchievementTest.php`:

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

it('lists predefined achievements for a non-custom category with link status', function (): void {
    $category = Category::factory()->create();
    $linked = Achievement::factory()->for($category)->create();
    $unlinked = Achievement::factory()->for($category)->create();

    $this->baby->achievements()->attach($linked, [
        'achieved_at' => '2026-02-15 14:30:00',
        'note' => 'During tummy time!',
    ]);

    $this->getJson("/api/v1/categories/{$category->uuid}/achievements")
        ->assertOk()
        ->assertJsonCount(2, 'data')
        ->assertJsonPath('data.0.link.note', 'During tummy time!')
        ->assertJsonPath('data.1.link', null);
});

it('excludes custom achievements from non-custom categories', function (): void {
    $category = Category::factory()->create();
    Achievement::factory()->for($category)->create(); // predefined
    Achievement::factory()->for($category)->create(['user_id' => $this->user->id]); // custom — should be excluded

    $this->getJson("/api/v1/categories/{$category->uuid}/achievements")
        ->assertOk()
        ->assertJsonCount(1, 'data');
});

it('lists only current user custom achievements for custom category', function (): void {
    $category = Category::factory()->custom()->create();
    $otherUser = User::factory()->create();

    Achievement::factory()->for($category)->create(['user_id' => $this->user->id]);
    Achievement::factory()->for($category)->create(['user_id' => $otherUser->id]); // should be excluded

    $this->getJson("/api/v1/categories/{$category->uuid}/achievements")
        ->assertOk()
        ->assertJsonCount(1, 'data');
});

it('returns achievement fields correctly', function (): void {
    $category = Category::factory()->create();
    $achievement = Achievement::factory()->for($category)->create([
        'name' => 'Head Control',
        'description' => 'Holds head steady.',
        'expected_age_min_months' => 1,
        'expected_age_max_months' => 4,
    ]);

    $this->getJson("/api/v1/categories/{$category->uuid}/achievements")
        ->assertOk()
        ->assertJsonPath('data.0.id', $achievement->uuid)
        ->assertJsonPath('data.0.name', 'Head Control')
        ->assertJsonPath('data.0.description', 'Holds head steady.')
        ->assertJsonPath('data.0.expected_age_min_months', 1)
        ->assertJsonPath('data.0.expected_age_max_months', 4)
        ->assertJsonPath('data.0.link', null);
});
```

- [ ] **Step 2: Run tests to verify they fail**

```bash
vendor/bin/sail artisan test --compact --filter=CategoryAchievementTest
```

Expected: FAIL.

- [ ] **Step 3: Create AchievementResource**

```bash
vendor/bin/sail artisan make:resource AchievementResource --no-interaction
```

Edit `app/Http/Resources/AchievementResource.php`.

The `link` field must always be present in the JSON output (either an object or `null`). The action will set a `babyLink` relation on each achievement — if loaded and not null, render it; otherwise return `null`.

```php
<?php

declare(strict_types=1);

namespace App\Http\Resources;

use App\Models\Achievement;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

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
            'name' => $this->name,
            'description' => $this->description,
            'expected_age_min_months' => $this->expected_age_min_months,
            'expected_age_max_months' => $this->expected_age_max_months,
            'link' => $this->relationLoaded('babyLink') && $this->babyLink ? [
                'id' => $this->babyLink->uuid,
                'achieved_at' => $this->babyLink->achieved_at->toIso8601ZuluString(),
                'note' => $this->babyLink->note,
            ] : null,
        ];
    }
}
```

- [ ] **Step 4: Create ListCategoryAchievements action**

```bash
vendor/bin/sail artisan make:class Actions/CategoryAchievement/ListCategoryAchievements --no-interaction
```

Edit `app/Actions/CategoryAchievement/ListCategoryAchievements.php`.

Uses 2 queries total: one for the category's achievements, one for the baby's links. Sets the `babyLink` relation on each achievement so the resource can render it.

```php
<?php

declare(strict_types=1);

namespace App\Actions\CategoryAchievement;

use App\Models\Achievement;
use App\Models\Baby;
use App\Models\BabyAchievement;
use App\Models\Category;
use App\Models\User;
use Illuminate\Database\Eloquent\Collection;

final readonly class ListCategoryAchievements
{
    /**
     * @return Collection<int, Achievement>
     */
    public function handle(Category $category, Baby $baby, User $user): Collection
    {
        $query = $category->achievements();

        if ($category->is_custom) {
            $query->customForUser($user);
        } else {
            $query->predefined();
        }

        $achievements = $query->get();

        $links = BabyAchievement::query()
            ->where('baby_id', $baby->id)
            ->whereIn('achievement_id', $achievements->pluck('id'))
            ->get()
            ->keyBy('achievement_id');

        return $achievements->each(function (Achievement $achievement) use ($links): void {
            $achievement->setRelation('babyLink', $links->get($achievement->id));
        });
    }
}
```

- [ ] **Step 5: Create CategoryAchievementController**

```bash
vendor/bin/sail artisan make:controller Api/V1/CategoryAchievementController --no-interaction
```

Edit `app/Http/Controllers/Api/V1/CategoryAchievementController.php`:

```php
<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Actions\CategoryAchievement\ListCategoryAchievements;
use App\Http\Resources\AchievementResource;
use App\Models\Baby;
use App\Models\Category;
use App\Models\User;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

final readonly class CategoryAchievementController
{
    public function index(Category $category, Baby $baby, ListCategoryAchievements $action): AnonymousResourceCollection
    {
        /** @var User $user */
        $user = $baby->user;

        return AchievementResource::collection($action->handle($category, $baby, $user));
    }
}
```

- [ ] **Step 6: Add route**

Add to `routes/api/v1.php` inside the `InjectDemoBaby` middleware group:

```php
use App\Http\Controllers\Api\V1\CategoryAchievementController;

Route::get('categories/{category}/achievements', [CategoryAchievementController::class, 'index']);
```

- [ ] **Step 7: Run tests to verify they pass**

```bash
vendor/bin/sail artisan test --compact --filter=CategoryAchievementTest
```

Expected: All 4 tests PASS.

- [ ] **Step 8: Run Pint**

```bash
vendor/bin/sail bin pint --dirty --format agent
```

- [ ] **Step 9: Commit**

```bash
git add app/Http/Resources/AchievementResource.php app/Actions/CategoryAchievement/ListCategoryAchievements.php app/Http/Controllers/Api/V1/CategoryAchievementController.php routes/api/v1.php tests/Feature/Api/V1/CategoryAchievementTest.php
git commit -m "feat(achievements): add GET /v1/categories/{category}/achievements endpoint"
```

---

## Chunk 4: Custom Achievements API

### Task 8: Custom Achievements — Create & Delete with Tests

**Files:**
- Create: `app/Http/Requests/Api/V1/StoreAchievementRequest.php`
- Create: `app/Actions/Achievement/CreateAchievement.php`
- Create: `app/Actions/Achievement/DeleteAchievement.php`
- Create: `app/Http/Controllers/Api/V1/AchievementController.php`
- Modify: `routes/api/v1.php`
- Create: `tests/Feature/Api/V1/AchievementTest.php`

- [ ] **Step 1: Write the failing tests**

```bash
vendor/bin/sail artisan make:test Api/V1/AchievementTest --pest --no-interaction
```

Edit `tests/Feature/Api/V1/AchievementTest.php`:

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
    $this->customCategory = Category::factory()->custom()->create();
});

it('creates a custom achievement', function (): void {
    $this->postJson('/api/v1/achievements', [
        'name' => 'First time at the park',
        'description' => 'Visited the local park',
    ])
        ->assertCreated()
        ->assertJsonPath('data.name', 'First time at the park')
        ->assertJsonPath('data.description', 'Visited the local park')
        ->assertJsonPath('data.link', null);

    $this->assertDatabaseHas('achievements', [
        'name' => 'First time at the park',
        'user_id' => $this->user->id,
        'category_id' => $this->customCategory->id,
    ]);
});

it('creates a custom achievement with optional fields', function (): void {
    $this->postJson('/api/v1/achievements', [
        'name' => 'First smile at grandma',
        'description' => null,
        'expected_age_min_months' => 2,
        'expected_age_max_months' => 6,
    ])
        ->assertCreated()
        ->assertJsonPath('data.expected_age_min_months', 2)
        ->assertJsonPath('data.expected_age_max_months', 6);
});

it('creates a custom achievement with a provided uuid', function (): void {
    $uuid = fake()->uuid();

    $this->postJson('/api/v1/achievements', [
        'uuid' => $uuid,
        'name' => 'Custom with UUID',
    ])
        ->assertCreated()
        ->assertJsonPath('data.id', $uuid);
});

it('validates required fields for custom achievement creation', function (): void {
    $this->postJson('/api/v1/achievements', [])
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['name']);
});

it('validates expected_age_max_months is gte expected_age_min_months', function (): void {
    $this->postJson('/api/v1/achievements', [
        'name' => 'Test',
        'expected_age_min_months' => 10,
        'expected_age_max_months' => 5,
    ])
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['expected_age_max_months']);
});

it('deletes a custom achievement', function (): void {
    $achievement = Achievement::factory()
        ->for($this->customCategory)
        ->create(['user_id' => $this->user->id]);

    $this->deleteJson("/api/v1/achievements/{$achievement->uuid}")
        ->assertNoContent();

    $this->assertDatabaseMissing('achievements', ['id' => $achievement->id]);
});

it('cannot delete a predefined achievement', function (): void {
    $category = Category::factory()->create();
    $achievement = Achievement::factory()->for($category)->create(['user_id' => null]);

    $this->deleteJson("/api/v1/achievements/{$achievement->uuid}")
        ->assertForbidden();

    $this->assertDatabaseHas('achievements', ['id' => $achievement->id]);
});

it('cannot delete another user custom achievement', function (): void {
    $otherUser = User::factory()->create();
    $achievement = Achievement::factory()
        ->for($this->customCategory)
        ->create(['user_id' => $otherUser->id]);

    $this->deleteJson("/api/v1/achievements/{$achievement->uuid}")
        ->assertForbidden();
});
```

- [ ] **Step 2: Run tests to verify they fail**

```bash
vendor/bin/sail artisan test --compact --filter=AchievementTest
```

Expected: FAIL.

- [ ] **Step 3: Create StoreAchievementRequest**

```bash
vendor/bin/sail artisan make:request Api/V1/StoreAchievementRequest --no-interaction
```

Edit `app/Http/Requests/Api/V1/StoreAchievementRequest.php`:

```php
<?php

declare(strict_types=1);

namespace App\Http\Requests\Api\V1;

use Illuminate\Foundation\Http\FormRequest;

final class StoreAchievementRequest extends FormRequest
{
    /**
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        return [
            'uuid' => ['sometimes', 'uuid', 'unique:achievements,uuid'],
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'expected_age_min_months' => ['nullable', 'integer', 'min:0', 'max:36'],
            'expected_age_max_months' => ['nullable', 'integer', 'min:0', 'max:36', 'gte:expected_age_min_months'],
        ];
    }
}
```

- [ ] **Step 4: Create CreateAchievement action**

```bash
vendor/bin/sail artisan make:class Actions/Achievement/CreateAchievement --no-interaction
```

Edit `app/Actions/Achievement/CreateAchievement.php`:

```php
<?php

declare(strict_types=1);

namespace App\Actions\Achievement;

use App\Models\Achievement;
use App\Models\Category;
use App\Models\User;

final readonly class CreateAchievement
{
    public function handle(
        User $user,
        string $name,
        ?string $description = null,
        ?int $expectedAgeMinMonths = null,
        ?int $expectedAgeMaxMonths = null,
        ?string $uuid = null,
    ): Achievement {
        $customCategory = Category::query()->where('is_custom', true)->firstOrFail();

        /** @var Achievement */
        return $customCategory->achievements()->create([
            'user_id' => $user->id,
            'name' => $name,
            'description' => $description,
            'expected_age_min_months' => $expectedAgeMinMonths,
            'expected_age_max_months' => $expectedAgeMaxMonths,
            ...($uuid !== null ? ['uuid' => $uuid] : []),
        ]);
    }
}
```

- [ ] **Step 5: Create DeleteAchievement action**

```bash
vendor/bin/sail artisan make:class Actions/Achievement/DeleteAchievement --no-interaction
```

Edit `app/Actions/Achievement/DeleteAchievement.php`:

```php
<?php

declare(strict_types=1);

namespace App\Actions\Achievement;

use App\Models\Achievement;

final readonly class DeleteAchievement
{
    public function handle(Achievement $achievement): void
    {
        $achievement->delete();
    }
}
```

- [ ] **Step 6: Create AchievementController**

```bash
vendor/bin/sail artisan make:controller Api/V1/AchievementController --no-interaction
```

Edit `app/Http/Controllers/Api/V1/AchievementController.php`:

```php
<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Actions\Achievement\CreateAchievement;
use App\Actions\Achievement\DeleteAchievement;
use App\Http\Requests\Api\V1\StoreAchievementRequest;
use App\Http\Resources\AchievementResource;
use App\Models\Achievement;
use App\Models\Baby;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Symfony\Component\HttpFoundation\Response;

final readonly class AchievementController
{
    public function store(StoreAchievementRequest $request, Baby $baby, CreateAchievement $action): JsonResponse
    {
        /** @var User $user */
        $user = $baby->user;

        /** @var string $name */
        $name = $request->validated('name');

        $achievement = $action->handle(
            user: $user,
            name: $name,
            description: $request->validated('description'),
            expectedAgeMinMonths: $request->validated('expected_age_min_months'),
            expectedAgeMaxMonths: $request->validated('expected_age_max_months'),
            uuid: $request->validated('uuid'),
        );

        return AchievementResource::make($achievement)
            ->response()
            ->setStatusCode(Response::HTTP_CREATED);
    }

    public function destroy(Achievement $achievement, Baby $baby, DeleteAchievement $action): Response
    {
        /** @var User $user */
        $user = $baby->user;

        if ($achievement->user_id === null || $achievement->user_id !== $user->id) {
            abort(Response::HTTP_FORBIDDEN);
        }

        $action->handle($achievement);

        return response()->noContent();
    }
}
```

- [ ] **Step 7: Add routes**

Add to `routes/api/v1.php` inside the `InjectDemoBaby` middleware group:

```php
use App\Http\Controllers\Api\V1\AchievementController;

Route::post('achievements', [AchievementController::class, 'store']);
Route::delete('achievements/{achievement}', [AchievementController::class, 'destroy']);
```

- [ ] **Step 8: Run tests to verify they pass**

```bash
vendor/bin/sail artisan test --compact --filter=AchievementTest
```

Expected: All 8 tests PASS.

- [ ] **Step 9: Run Pint**

```bash
vendor/bin/sail bin pint --dirty --format agent
```

- [ ] **Step 10: Commit**

```bash
git add app/Http/Requests/Api/V1/StoreAchievementRequest.php app/Actions/Achievement/CreateAchievement.php app/Actions/Achievement/DeleteAchievement.php app/Http/Controllers/Api/V1/AchievementController.php routes/api/v1.php tests/Feature/Api/V1/AchievementTest.php
git commit -m "feat(achievements): add POST/DELETE /v1/achievements endpoints for custom achievements"
```

---

## Chunk 5: Achievement Linking API

### Task 9: Achievement Linking — Link, Update, Unlink with Tests

**Files:**
- Create: `app/Http/Requests/Api/V1/StoreAchievementLinkRequest.php`
- Create: `app/Http/Requests/Api/V1/UpdateAchievementLinkRequest.php`
- Create: `app/Actions/AchievementLink/LinkAchievement.php`
- Create: `app/Actions/AchievementLink/UpdateAchievementLink.php`
- Create: `app/Actions/AchievementLink/UnlinkAchievement.php`
- Create: `app/Http/Resources/BabyAchievementResource.php`
- Create: `app/Http/Controllers/Api/V1/AchievementLinkController.php`
- Modify: `routes/api/v1.php`
- Create: `tests/Feature/Api/V1/AchievementLinkTest.php`

- [ ] **Step 1: Write the failing tests**

```bash
vendor/bin/sail artisan make:test Api/V1/AchievementLinkTest --pest --no-interaction
```

Edit `tests/Feature/Api/V1/AchievementLinkTest.php`:

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
    $this->category = Category::factory()->create();
});

it('links an achievement to a baby', function (): void {
    $achievement = Achievement::factory()->for($this->category)->create();

    $this->postJson("/api/v1/achievements/{$achievement->uuid}/link", [
        'achieved_at' => '2026-02-15T14:30:00',
        'note' => 'During tummy time!',
    ])
        ->assertCreated()
        ->assertJsonPath('data.achieved_at', '2026-02-15T14:30:00Z')
        ->assertJsonPath('data.note', 'During tummy time!');

    $this->assertDatabaseHas('baby_achievement', [
        'baby_id' => $this->baby->id,
        'achievement_id' => $achievement->id,
        'note' => 'During tummy time!',
    ]);
});

it('links an achievement with auto-generated uuid', function (): void {
    $achievement = Achievement::factory()->for($this->category)->create();

    $this->postJson("/api/v1/achievements/{$achievement->uuid}/link", [
        'achieved_at' => '2026-02-15T14:30:00',
    ])
        ->assertCreated()
        ->assertJsonStructure(['data' => ['id']]);
});

it('links an achievement with a provided uuid', function (): void {
    $achievement = Achievement::factory()->for($this->category)->create();
    $uuid = fake()->uuid();

    $this->postJson("/api/v1/achievements/{$achievement->uuid}/link", [
        'uuid' => $uuid,
        'achieved_at' => '2026-02-15T14:30:00',
    ])
        ->assertCreated()
        ->assertJsonPath('data.id', $uuid);
});

it('prevents duplicate links for the same baby and achievement', function (): void {
    $achievement = Achievement::factory()->for($this->category)->create();
    $this->baby->achievements()->attach($achievement, ['achieved_at' => now()]);

    $this->postJson("/api/v1/achievements/{$achievement->uuid}/link", [
        'achieved_at' => '2026-03-01T10:00:00',
    ])
        ->assertUnprocessable();
});

it('validates required fields for linking', function (): void {
    $achievement = Achievement::factory()->for($this->category)->create();

    $this->postJson("/api/v1/achievements/{$achievement->uuid}/link", [])
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['achieved_at']);
});

it('updates a linked achievement', function (): void {
    $achievement = Achievement::factory()->for($this->category)->create();
    $this->baby->achievements()->attach($achievement, [
        'achieved_at' => '2026-02-15 14:30:00',
        'note' => 'Old note',
    ]);

    $this->putJson("/api/v1/achievements/{$achievement->uuid}/link", [
        'achieved_at' => '2026-03-01T10:00:00',
        'note' => 'Updated note',
    ])
        ->assertOk()
        ->assertJsonPath('data.note', 'Updated note');

    $this->assertDatabaseHas('baby_achievement', [
        'baby_id' => $this->baby->id,
        'achievement_id' => $achievement->id,
        'note' => 'Updated note',
    ]);
});

it('returns 404 when updating a non-linked achievement', function (): void {
    $achievement = Achievement::factory()->for($this->category)->create();

    $this->putJson("/api/v1/achievements/{$achievement->uuid}/link", [
        'achieved_at' => '2026-03-01T10:00:00',
    ])
        ->assertNotFound();
});

it('unlinks an achievement from a baby', function (): void {
    $achievement = Achievement::factory()->for($this->category)->create();
    $this->baby->achievements()->attach($achievement, ['achieved_at' => now()]);

    $this->deleteJson("/api/v1/achievements/{$achievement->uuid}/link")
        ->assertNoContent();

    $this->assertDatabaseMissing('baby_achievement', [
        'baby_id' => $this->baby->id,
        'achievement_id' => $achievement->id,
    ]);
});

it('returns 404 when unlinking a non-linked achievement', function (): void {
    $achievement = Achievement::factory()->for($this->category)->create();

    $this->deleteJson("/api/v1/achievements/{$achievement->uuid}/link")
        ->assertNotFound();
});
```

- [ ] **Step 2: Run tests to verify they fail**

```bash
vendor/bin/sail artisan test --compact --filter=AchievementLinkTest
```

Expected: FAIL.

- [ ] **Step 3: Create StoreAchievementLinkRequest**

```bash
vendor/bin/sail artisan make:request Api/V1/StoreAchievementLinkRequest --no-interaction
```

Edit `app/Http/Requests/Api/V1/StoreAchievementLinkRequest.php`:

```php
<?php

declare(strict_types=1);

namespace App\Http\Requests\Api\V1;

use Illuminate\Foundation\Http\FormRequest;

final class StoreAchievementLinkRequest extends FormRequest
{
    /**
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        return [
            'uuid' => ['sometimes', 'uuid', 'unique:baby_achievement,uuid'],
            'achieved_at' => ['required', 'date'],
            'note' => ['nullable', 'string'],
        ];
    }
}
```

- [ ] **Step 4: Create UpdateAchievementLinkRequest**

```bash
vendor/bin/sail artisan make:request Api/V1/UpdateAchievementLinkRequest --no-interaction
```

Edit `app/Http/Requests/Api/V1/UpdateAchievementLinkRequest.php`:

```php
<?php

declare(strict_types=1);

namespace App\Http\Requests\Api\V1;

use Illuminate\Foundation\Http\FormRequest;

final class UpdateAchievementLinkRequest extends FormRequest
{
    /**
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        return [
            'achieved_at' => ['sometimes', 'required', 'date'],
            'note' => ['nullable', 'string'],
        ];
    }
}
```

- [ ] **Step 5: Create LinkAchievement action**

```bash
vendor/bin/sail artisan make:class Actions/AchievementLink/LinkAchievement --no-interaction
```

Edit `app/Actions/AchievementLink/LinkAchievement.php`:

```php
<?php

declare(strict_types=1);

namespace App\Actions\AchievementLink;

use App\Models\Achievement;
use App\Models\Baby;
use App\Models\BabyAchievement;

final readonly class LinkAchievement
{
    public function handle(
        Baby $baby,
        Achievement $achievement,
        string $achievedAt,
        ?string $note = null,
        ?string $uuid = null,
    ): BabyAchievement {
        $baby->achievements()->attach($achievement, [
            'achieved_at' => $achievedAt,
            'note' => $note,
            ...($uuid !== null ? ['uuid' => $uuid] : []),
        ]);

        /** @var BabyAchievement */
        return BabyAchievement::query()
            ->where('baby_id', $baby->id)
            ->where('achievement_id', $achievement->id)
            ->firstOrFail();
    }
}
```

- [ ] **Step 6: Create UpdateAchievementLink action**

```bash
vendor/bin/sail artisan make:class Actions/AchievementLink/UpdateAchievementLink --no-interaction
```

Edit `app/Actions/AchievementLink/UpdateAchievementLink.php`:

```php
<?php

declare(strict_types=1);

namespace App\Actions\AchievementLink;

use App\Models\BabyAchievement;

final readonly class UpdateAchievementLink
{
    /**
     * @param array<string, mixed> $data
     */
    public function handle(BabyAchievement $link, array $data): BabyAchievement
    {
        $link->update($data);

        return $link->refresh();
    }
}
```

- [ ] **Step 7: Create UnlinkAchievement action**

```bash
vendor/bin/sail artisan make:class Actions/AchievementLink/UnlinkAchievement --no-interaction
```

Edit `app/Actions/AchievementLink/UnlinkAchievement.php`:

```php
<?php

declare(strict_types=1);

namespace App\Actions\AchievementLink;

use App\Models\Achievement;
use App\Models\Baby;

final readonly class UnlinkAchievement
{
    public function handle(Baby $baby, Achievement $achievement): void
    {
        $baby->achievements()->detach($achievement);
    }
}
```

- [ ] **Step 8: Create BabyAchievementResource**

```bash
vendor/bin/sail artisan make:resource BabyAchievementResource --no-interaction
```

Edit `app/Http/Resources/BabyAchievementResource.php`:

```php
<?php

declare(strict_types=1);

namespace App\Http\Resources;

use App\Models\BabyAchievement;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

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
            'achieved_at' => $this->achieved_at->toIso8601ZuluString(),
            'note' => $this->note,
        ];
    }
}
```

- [ ] **Step 9: Create AchievementLinkController**

```bash
vendor/bin/sail artisan make:controller Api/V1/AchievementLinkController --no-interaction
```

Edit `app/Http/Controllers/Api/V1/AchievementLinkController.php`:

```php
<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Actions\AchievementLink\LinkAchievement;
use App\Actions\AchievementLink\UnlinkAchievement;
use App\Actions\AchievementLink\UpdateAchievementLink;
use App\Http\Requests\Api\V1\StoreAchievementLinkRequest;
use App\Http\Requests\Api\V1\UpdateAchievementLinkRequest;
use App\Http\Resources\BabyAchievementResource;
use App\Models\Achievement;
use App\Models\Baby;
use App\Models\BabyAchievement;
use Illuminate\Http\JsonResponse;
use Symfony\Component\HttpFoundation\Response;

final readonly class AchievementLinkController
{
    public function store(
        StoreAchievementLinkRequest $request,
        Achievement $achievement,
        Baby $baby,
        LinkAchievement $action,
    ): JsonResponse {
        $existing = BabyAchievement::query()
            ->where('baby_id', $baby->id)
            ->where('achievement_id', $achievement->id)
            ->exists();

        if ($existing) {
            abort(Response::HTTP_UNPROCESSABLE_ENTITY, 'Achievement already linked to this baby.');
        }

        /** @var string $achievedAt */
        $achievedAt = $request->validated('achieved_at');

        $link = $action->handle(
            baby: $baby,
            achievement: $achievement,
            achievedAt: $achievedAt,
            note: $request->validated('note'),
            uuid: $request->validated('uuid'),
        );

        return BabyAchievementResource::make($link)
            ->response()
            ->setStatusCode(Response::HTTP_CREATED);
    }

    public function update(
        UpdateAchievementLinkRequest $request,
        Achievement $achievement,
        Baby $baby,
        UpdateAchievementLink $action,
    ): BabyAchievementResource {
        $link = BabyAchievement::query()
            ->where('baby_id', $baby->id)
            ->where('achievement_id', $achievement->id)
            ->firstOrFail();

        $action->handle($link, $request->validated());

        return BabyAchievementResource::make($link);
    }

    public function destroy(
        Achievement $achievement,
        Baby $baby,
        UnlinkAchievement $action,
    ): Response {
        $exists = BabyAchievement::query()
            ->where('baby_id', $baby->id)
            ->where('achievement_id', $achievement->id)
            ->exists();

        if (! $exists) {
            abort(Response::HTTP_NOT_FOUND);
        }

        $action->handle($baby, $achievement);

        return response()->noContent();
    }
}
```

- [ ] **Step 10: Add routes**

Add to `routes/api/v1.php` inside the `InjectDemoBaby` middleware group:

```php
use App\Http\Controllers\Api\V1\AchievementLinkController;

Route::post('achievements/{achievement}/link', [AchievementLinkController::class, 'store']);
Route::put('achievements/{achievement}/link', [AchievementLinkController::class, 'update']);
Route::delete('achievements/{achievement}/link', [AchievementLinkController::class, 'destroy']);
```

- [ ] **Step 11: Run tests to verify they pass**

```bash
vendor/bin/sail artisan test --compact --filter=AchievementLinkTest
```

Expected: All 9 tests PASS.

- [ ] **Step 12: Run Pint**

```bash
vendor/bin/sail bin pint --dirty --format agent
```

- [ ] **Step 13: Commit**

```bash
git add app/Http/Requests/Api/V1/StoreAchievementLinkRequest.php app/Http/Requests/Api/V1/UpdateAchievementLinkRequest.php app/Actions/AchievementLink/ app/Http/Controllers/Api/V1/AchievementLinkController.php app/Http/Resources/BabyAchievementResource.php routes/api/v1.php tests/Feature/Api/V1/AchievementLinkTest.php
git commit -m "feat(achievements): add POST/PUT/DELETE /v1/achievements/{achievement}/link endpoints"
```

---

## Chunk 6: Final Verification

### Task 10: Run Full Test Suite & Pint

- [ ] **Step 1: Run all tests**

```bash
vendor/bin/sail artisan test --compact
```

Expected: All tests pass, including existing tests (no regressions).

- [ ] **Step 2: Run Pint on all changed files**

```bash
vendor/bin/sail bin pint --dirty --format agent
```

Expected: No formatting issues, or auto-fixed.

- [ ] **Step 3: Final commit if Pint made changes**

```bash
git add -A
git commit -m "style: apply Pint formatting"
```

- [ ] **Step 4: Verify seeder still works on fresh database**

```bash
vendor/bin/sail artisan migrate:fresh --seed --seeder=AchievementSeeder
vendor/bin/sail artisan tinker --execute="echo 'Categories: ' . App\Models\Category::count() . ', Achievements: ' . App\Models\Achievement::count();"
```

Expected: `Categories: 6, Achievements: 56` (AchievementSeeder calls CategorySeeder internally)
