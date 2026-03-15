# Active Baby Middleware Routing — Implementation Plan

> **For agentic workers:** REQUIRED: Use superpowers:subagent-driven-development (if subagents available) or superpowers:executing-plans to implement this plan. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Replace `babies/{baby}` URL nesting with a `ResolveActiveBaby` middleware that binds the user's only baby into the service container, yielding flat API routes.

**Architecture:** A new `ResolveActiveBaby` middleware resolves the authenticated user's baby via `$user->babies()->first()` and binds it as `app()->instance(Baby::class, $baby)`. Controllers receive `Baby` via type-hinted DI. Baby CRUD is reduced to a single `POST /babies` store endpoint.

**Tech Stack:** Laravel 12, Pest 4, Sanctum

---

## Chunk 1: Middleware + Routing Foundation

### Task 1: Create `ResolveActiveBaby` middleware

**Files:**
- Create: `app/Http/Middleware/ResolveActiveBaby.php`
- Modify: `bootstrap/app.php`

- [ ] **Step 1: Create the middleware**

```php
<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Models\Baby;
use Closure;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

final readonly class ResolveActiveBaby
{
    public function handle(Request $request, Closure $next): Response|JsonResponse
    {
        $baby = $request->user()->babies()->first();

        if (! $baby) {
            return response()->json(['message' => 'No active baby profile'], Response::HTTP_FORBIDDEN);
        }

        app()->instance(Baby::class, $baby);

        return $next($request);
    }
}
```

- [ ] **Step 2: Register middleware alias in bootstrap/app.php**

In `bootstrap/app.php`, inside `->withMiddleware()`, add:

```php
$middleware->alias([
    'resolve.active.baby' => \App\Http\Middleware\ResolveActiveBaby::class,
]);
```

- [ ] **Step 3: Commit**

```bash
git add app/Http/Middleware/ResolveActiveBaby.php bootstrap/app.php
git commit -m "feat(middleware): add ResolveActiveBaby middleware with container binding"
```

---

### Task 2: Create `StoreBabyController` (invokable)

**Files:**
- Create: `app/Http/Controllers/Api/V1/StoreBabyController.php`

- [ ] **Step 1: Write failing test for POST /babies**

In `tests/Feature/Api/V1/BabyTest.php`, replace the entire file with tests for the single store endpoint:

```php
<?php

declare(strict_types=1);

use App\Models\User;

beforeEach(function (): void {
    $this->user = User::factory()->create();
});

it('requires authentication to create a baby', function (): void {
    $this->postJson('/api/v1/babies', ['nickname' => 'Lila'])
        ->assertUnauthorized();
});

it('creates a new baby', function (): void {
    $this->actingAs($this->user)
        ->postJson('/api/v1/babies', ['nickname' => 'Lila'])
        ->assertCreated()
        ->assertJsonPath('data.nickname', 'Lila');

    $this->assertDatabaseHas('babies', [
        'user_id' => $this->user->id,
        'nickname' => 'Lila',
    ]);
});

it('validates nickname is required when creating', function (): void {
    $this->actingAs($this->user)
        ->postJson('/api/v1/babies', [])
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['nickname']);
});

it('validates nickname max length when creating', function (): void {
    $this->actingAs($this->user)
        ->postJson('/api/v1/babies', ['nickname' => str_repeat('a', 256)])
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['nickname']);
});
```

- [ ] **Step 2: Run test to verify it fails**

Run: `vendor/bin/sail artisan test --compact --filter=BabyTest`
Expected: FAIL (route not found once old controller removed)

- [ ] **Step 3: Create invokable StoreBabyController**

```php
<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Actions\Baby\CreateBaby;
use App\Http\Requests\Api\V1\StoreBabyRequest;
use App\Http\Resources\BabyResource;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Symfony\Component\HttpFoundation\Response;

final readonly class StoreBabyController
{
    public function __invoke(StoreBabyRequest $request, CreateBaby $action): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();

        /** @var string $nickname */
        $nickname = $request->validated('nickname');

        $baby = $action->handle($user, $nickname);

        return BabyResource::make($baby)
            ->response()
            ->setStatusCode(Response::HTTP_CREATED);
    }
}
```

- [ ] **Step 4: Commit**

```bash
git add app/Http/Controllers/Api/V1/StoreBabyController.php tests/Feature/Api/V1/BabyTest.php
git commit -m "feat(baby): add invokable StoreBabyController and slim BabyTest to store-only"
```

---

### Task 3: Rename category/achievement controllers

**Files:**
- Rename: `app/Http/Controllers/Api/V1/ListCategoryController.php` → `AchievementCategoryController.php`
- Rename: `app/Http/Controllers/Api/V1/ListAchievementController.php` → `AchievementController.php`

- [ ] **Step 1: Create AchievementCategoryController**

```php
<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Actions\Category\ListCategories;
use App\Http\Resources\CategoryResource;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

final readonly class AchievementCategoryController
{
    public function index(ListCategories $action): AnonymousResourceCollection
    {
        return CategoryResource::collection($action->handle());
    }
}
```

- [ ] **Step 2: Create AchievementController**

```php
<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Actions\Achievement\ListAchievements;
use App\Http\Requests\Api\V1\ListAchievementRequest;
use App\Http\Resources\AchievementResource;
use App\Models\Category;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

final readonly class AchievementController
{
    public function index(ListAchievementRequest $request, ListAchievements $action): AnonymousResourceCollection
    {
        $category = $request->validated('category')
            ? Category::query()->where('uuid', $request->validated('category'))->first()
            : null;

        return AchievementResource::collection($action->handle($category));
    }
}
```

- [ ] **Step 3: Delete old controllers**

Delete `app/Http/Controllers/Api/V1/ListCategoryController.php` and `app/Http/Controllers/Api/V1/ListAchievementController.php`.

- [ ] **Step 4: Commit**

```bash
git add app/Http/Controllers/Api/V1/AchievementCategoryController.php app/Http/Controllers/Api/V1/AchievementController.php
git rm app/Http/Controllers/Api/V1/ListCategoryController.php app/Http/Controllers/Api/V1/ListAchievementController.php
git commit -m "refactor(controllers): rename ListCategoryController and ListAchievementController to resource-style names"
```

---

### Task 4: Update `BabyAchievementController` to use DI-resolved Baby

**Files:**
- Modify: `app/Http/Controllers/Api/V1/BabyAchievementController.php`
- Modify: `app/Http/Requests/Api/V1/StoreBabyAchievementRequest.php`

- [ ] **Step 1: Update controller — Baby comes from container, Achievement from route on store**

```php
<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Actions\BabyAchievement\CreateBabyAchievement;
use App\Actions\BabyAchievement\DeleteBabyAchievement;
use App\Actions\BabyAchievement\ListBabyAchievements;
use App\Actions\BabyAchievement\UpdateBabyAchievement as UpdateBabyAchievementAction;
use App\Http\Requests\Api\V1\ListBabyAchievementRequest;
use App\Http\Requests\Api\V1\StoreBabyAchievementRequest;
use App\Http\Requests\Api\V1\UpdateBabyAchievementRequest;
use App\Http\Resources\BabyAchievementResource;
use App\Models\Achievement;
use App\Models\Baby;
use App\Models\BabyAchievement;
use App\Models\Category;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Symfony\Component\HttpFoundation\Response;

final readonly class BabyAchievementController
{
    public function index(ListBabyAchievementRequest $request, Baby $baby, ListBabyAchievements $action): AnonymousResourceCollection
    {
        $category = $request->validated('category')
            ? Category::query()->where('uuid', $request->validated('category'))->first()
            : null;

        return BabyAchievementResource::collection($action->handle($baby, $category));
    }

    public function store(StoreBabyAchievementRequest $request, Achievement $achievement, Baby $baby, CreateBabyAchievement $action): JsonResponse
    {
        /** @var string|null $note */
        $note = $request->validated('note');

        /** @var string|null $uuid */
        $uuid = $request->validated('uuid');

        $result = $action->handle(
            baby: $baby,
            achievement: $achievement,
            note: $note,
            uuid: $uuid,
        );

        if ($result->isErr()) {
            return response()->json([
                'message' => $result->error(),
            ], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        return BabyAchievementResource::make($result->value())
            ->response()
            ->setStatusCode(Response::HTTP_CREATED);
    }

    public function update(
        UpdateBabyAchievementRequest $request,
        BabyAchievement $babyAchievement,
        UpdateBabyAchievementAction $action,
    ): BabyAchievementResource {
        $action->handle($babyAchievement, $request->validated());

        return BabyAchievementResource::make($babyAchievement->load('achievement'));
    }

    public function destroy(BabyAchievement $babyAchievement, DeleteBabyAchievement $action): Response
    {
        $action->handle($babyAchievement);

        return response()->noContent();
    }
}
```

- [ ] **Step 2: Update StoreBabyAchievementRequest — remove achievement_id rule**

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
            'note' => ['nullable', 'string'],
        ];
    }
}
```

- [ ] **Step 3: Commit**

```bash
git add app/Http/Controllers/Api/V1/BabyAchievementController.php app/Http/Requests/Api/V1/StoreBabyAchievementRequest.php
git commit -m "refactor(baby-achievement): resolve Baby from container, Achievement from route param"
```

---

### Task 5: Update `StoreMilkGoalRequest` and milk controllers

**Files:**
- Modify: `app/Http/Requests/Api/V1/StoreMilkGoalRequest.php`

Controller signatures for `MilkActivityController` and `MilkGoalController` already accept `Baby $baby` via type-hint — no changes needed there. The container binding from middleware handles resolution automatically.

- [ ] **Step 1: Update StoreMilkGoalRequest — resolve Baby from container instead of route**

The current code uses `$this->route('baby')` which will be `null` under flat routes. Replace with `app(Baby::class)`:

```php
<?php

declare(strict_types=1);

namespace App\Http\Requests\Api\V1;

use App\Models\Baby;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

final class StoreMilkGoalRequest extends FormRequest
{
    /**
     * @return array<string, array<mixed>>
     */
    public function rules(): array
    {
        /** @var Baby $baby */
        $baby = app(Baby::class);

        return [
            'uuid' => ['sometimes', 'uuid', 'unique:milk_goals,uuid'],
            'date' => ['required', 'date_format:Y-m-d', Rule::unique('milk_goals')->where('baby_id', $baby->id)],
            'goal' => ['required', 'integer', 'min:1'],
        ];
    }
}
```

- [ ] **Step 2: Commit**

```bash
git add app/Http/Requests/Api/V1/StoreMilkGoalRequest.php
git commit -m "fix(milk-goal): resolve Baby from container in StoreMilkGoalRequest"
```

---

### Task 6: Rewrite routes

**Files:**
- Modify: `routes/api/v1.php`

- [ ] **Step 1: Rewrite the full route file**

```php
<?php

declare(strict_types=1);

use App\Http\Controllers\Api\V1\AchievementCategoryController;
use App\Http\Controllers\Api\V1\AchievementController;
use App\Http\Controllers\Api\V1\BabyAchievementController;
use App\Http\Controllers\Api\V1\MilkActivityController;
use App\Http\Controllers\Api\V1\MilkGoalController;
use App\Http\Controllers\Api\V1\MilkMeasureController;
use App\Http\Controllers\Api\V1\StoreBabyController;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::get('/health', fn (): JsonResponse => response()->json([
    'status' => 'healthy',
    'timestamp' => Illuminate\Support\Facades\Date::now()->toIso8601String(),
]))->name('api.v1.health');

Route::middleware('auth:sanctum')->group(function (): void {
    Route::get('/user', fn (Request $request): mixed => $request->user())->name('api.v1.user');

    Route::post('babies', StoreBabyController::class)->name('babies.store');
});

Route::middleware(['auth:sanctum', 'resolve.active.baby'])->group(function (): void {
    // Achievement categories & achievements (read-only)
    Route::apiResource('achievement-categories', AchievementCategoryController::class)
        ->only(['index']);

    Route::apiResource('achievements', AchievementController::class)
        ->only(['index']);

    // Baby achievements
    Route::post('baby-achievements/{achievement}', [BabyAchievementController::class, 'store'])
        ->name('baby-achievements.store');

    Route::apiResource('baby-achievements', BabyAchievementController::class)
        ->only(['index', 'update', 'destroy']);

    // Milk tracking
    Route::get('milk-activity', MilkActivityController::class)->name('milk-activity.index');

    Route::apiResource('milk-goals', MilkGoalController::class);
    Route::apiResource('milk-goals.measures', MilkMeasureController::class)->scoped();
});
```

- [ ] **Step 2: Run Pint**

Run: `vendor/bin/sail bin pint --dirty --format agent`

- [ ] **Step 3: Commit**

```bash
git add routes/api/v1.php
git commit -m "refactor(routing): flatten routes with resolve.active.baby middleware"
```

---

## Chunk 2: Tests + Cleanup

### Task 7: Write middleware test

**Files:**
- Create: `tests/Feature/Api/V1/ResolveActiveBabyMiddlewareTest.php`

- [ ] **Step 1: Write middleware tests**

```php
<?php

declare(strict_types=1);

use App\Models\Baby;
use App\Models\User;

it('returns 403 when user has no baby profile', function (): void {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->getJson('/api/v1/achievement-categories')
        ->assertForbidden()
        ->assertJsonPath('message', 'No active baby profile');
});

it('allows access when user has a baby profile', function (): void {
    $user = User::factory()->create();
    Baby::factory()->for($user)->create();

    $this->actingAs($user)
        ->getJson('/api/v1/achievement-categories')
        ->assertOk();
});
```

- [ ] **Step 2: Run test to verify it passes**

Run: `vendor/bin/sail artisan test --compact --filter=ResolveActiveBabyMiddleware`
Expected: PASS

- [ ] **Step 3: Commit**

```bash
git add tests/Feature/Api/V1/ResolveActiveBabyMiddlewareTest.php
git commit -m "test(middleware): add ResolveActiveBaby middleware tests"
```

---

### Task 8: Update Category and Achievement tests for auth + new routes

**Files:**
- Modify: `tests/Feature/Api/V1/ListCategoryTest.php`
- Modify: `tests/Feature/Api/V1/ListAchievementTest.php`

These tests currently have no `actingAs()` and manually bind Baby via `app()->instance()`. After the refactor, routes require `auth:sanctum` + `resolve.active.baby`. The middleware handles the container binding, so manual binding is removed.

- [ ] **Step 1: Update ListCategoryTest — add auth, update URL**

```php
<?php

declare(strict_types=1);

use App\Models\Baby;
use App\Models\Category;
use App\Models\User;

beforeEach(function (): void {
    $this->user = User::factory()->create();
    $this->baby = Baby::factory()->for($this->user)->create();
    $this->actingAs($this->user);
});

it('lists all categories', function (): void {
    $categories = Category::factory()->count(3)->create();

    $this->getJson('/api/v1/achievement-categories')
        ->assertOk()
        ->assertJsonCount(3, 'data')
        ->assertJsonPath('data.0.id', $categories->first()->uuid)
        ->assertJsonPath('data.0.name', $categories->first()->name)
        ->assertJsonPath('data.0.slug', $categories->first()->slug);
});

it('returns empty list when no categories exist', function (): void {
    $this->getJson('/api/v1/achievement-categories')
        ->assertOk()
        ->assertJsonCount(0, 'data');
});
```

- [ ] **Step 2: Update ListAchievementTest — add auth, update URL**

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
    $this->actingAs($this->user);
});

it('lists all achievements', function (): void {
    $category = Category::factory()->create();
    Achievement::factory()->count(3)->for($category)->create();

    $this->getJson('/api/v1/achievements')
        ->assertOk()
        ->assertJsonCount(3, 'data');
});

it('returns empty list when no achievements exist', function (): void {
    $this->getJson('/api/v1/achievements')
        ->assertOk()
        ->assertJsonCount(0, 'data');
});

it('filters achievements by category', function (): void {
    $category1 = Category::factory()->create();
    $category2 = Category::factory()->create();
    Achievement::factory()->count(2)->for($category1)->create();
    Achievement::factory()->count(3)->for($category2)->create();

    $this->getJson('/api/v1/achievements?category='.$category1->uuid)
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

it('returns 422 for non-existent category uuid', function (): void {
    $this->getJson('/api/v1/achievements?category='.fake()->uuid())
        ->assertUnprocessable();
});
```

- [ ] **Step 3: Run tests**

Run: `vendor/bin/sail artisan test --compact --filter=ListCategory && vendor/bin/sail artisan test --compact --filter=ListAchievement`
Expected: PASS

- [ ] **Step 4: Commit**

```bash
git add tests/Feature/Api/V1/ListCategoryTest.php tests/Feature/Api/V1/ListAchievementTest.php
git commit -m "test(categories,achievements): add auth and update URLs for active baby middleware"
```

---

### Task 9: Update BabyAchievementTest for new routes


**Files:**
- Modify: `tests/Feature/Api/V1/BabyAchievementTest.php`

- [ ] **Step 1: Rewrite test file for new route structure**

Key changes:
- `beforeEach` must `actingAs($this->user)` since routes require auth
- Index: `GET /api/v1/baby-achievements` (no baby in URL)
- Store: `POST /api/v1/baby-achievements/{achievement_uuid}` (achievement in URL, no `achievement_id` in body)
- Update/Destroy: `PUT/DELETE /api/v1/baby-achievements/{uuid}` (unchanged)
- Remove `achievement_id` validation tests (no longer in request body)

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
    $this->category = Category::factory()->create();
    $this->actingAs($this->user);
});

// --- Index ---

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

    $this->getJson('/api/v1/baby-achievements?category='.$this->category->uuid)
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

// --- Store ---

it('creates a baby achievement', function (): void {
    $achievement = Achievement::factory()->for($this->category)->create();

    $this->postJson('/api/v1/baby-achievements/'.$achievement->uuid, [
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

    $this->postJson('/api/v1/baby-achievements/'.$achievement->uuid)
        ->assertCreated()
        ->assertJsonPath('data.note', null);
});

it('creates a baby achievement with provided uuid', function (): void {
    $achievement = Achievement::factory()->for($this->category)->create();
    $uuid = fake()->uuid();

    $this->postJson('/api/v1/baby-achievements/'.$achievement->uuid, [
        'uuid' => $uuid,
    ])
        ->assertCreated()
        ->assertJsonPath('data.id', $uuid);
});

it('prevents duplicate baby achievements for the same achievement', function (): void {
    $achievement = Achievement::factory()->for($this->category)->create();
    $this->baby->achievements()->attach($achievement, ['note' => null]);

    $this->postJson('/api/v1/baby-achievements/'.$achievement->uuid)
        ->assertUnprocessable();
});

it('returns 404 when storing with non-existent achievement', function (): void {
    $this->postJson('/api/v1/baby-achievements/'.fake()->uuid())
        ->assertNotFound();
});

// --- Update ---

it('updates a baby achievement note', function (): void {
    $achievement = Achievement::factory()->for($this->category)->create();
    $this->baby->achievements()->attach($achievement, ['note' => 'Old note']);
    $link = BabyAchievement::query()->first();

    $this->putJson('/api/v1/baby-achievements/'.$link->uuid, [
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

    $this->putJson('/api/v1/baby-achievements/'.$link->uuid, [
        'note' => null,
    ])
        ->assertOk()
        ->assertJsonPath('data.note', null);
});

// --- Destroy ---

it('deletes a baby achievement', function (): void {
    $achievement = Achievement::factory()->for($this->category)->create();
    $this->baby->achievements()->attach($achievement, ['note' => null]);
    $link = BabyAchievement::query()->first();

    $this->deleteJson('/api/v1/baby-achievements/'.$link->uuid)
        ->assertNoContent();

    $this->assertDatabaseMissing('baby_achievement', ['id' => $link->id]);
});

it('returns 404 when deleting non-existent baby achievement', function (): void {
    $this->deleteJson('/api/v1/baby-achievements/'.fake()->uuid())
        ->assertNotFound();
});
```

- [ ] **Step 2: Run tests**

Run: `vendor/bin/sail artisan test --compact --filter=BabyAchievementTest`
Expected: PASS

- [ ] **Step 3: Commit**

```bash
git add tests/Feature/Api/V1/BabyAchievementTest.php
git commit -m "test(baby-achievement): update tests for flat routes with active baby middleware"
```

---

### Task 10: Update milk tests for new routes

**Files:**
- Modify: `tests/Feature/Api/V1/MilkGoalTest.php`
- Modify: `tests/Feature/Api/V1/MilkMeasureTest.php`
- Modify: `tests/Feature/Api/V1/MilkActivityTest.php`

- [ ] **Step 1: Update milk test URLs**

In all three test files:
- Add `$this->actingAs($this->user);` to `beforeEach`
- Replace `/api/v1/babies/{baby_uuid}/milk-goals` → `/api/v1/milk-goals`
- Replace `/api/v1/babies/{baby_uuid}/milk-activity` → `/api/v1/milk-activity`
- Shallow routes for `milk-goals/{milkGoal}` and `milk-goals/{milkGoal}/measures` stay the same

- [ ] **Step 2: Run all milk tests**

Run: `vendor/bin/sail artisan test --compact --filter=Milk`
Expected: PASS

- [ ] **Step 3: Commit**

```bash
git add tests/Feature/Api/V1/MilkGoalTest.php tests/Feature/Api/V1/MilkMeasureTest.php tests/Feature/Api/V1/MilkActivityTest.php
git commit -m "test(milk): update tests for flat routes with active baby middleware"
```

---

### Task 11: Delete unused files

**Files:**
- Delete: `app/Http/Controllers/Api/V1/BabyController.php`
- Delete: `app/Http/Requests/Api/V1/UpdateBabyRequest.php`
- Delete: `app/Actions/Baby/ListBabies.php`
- Delete: `app/Actions/Baby/UpdateBaby.php`
- Delete: `app/Actions/Baby/DeleteBaby.php`

- [ ] **Step 1: Delete unused files**

```bash
git rm app/Http/Controllers/Api/V1/BabyController.php
git rm app/Http/Requests/Api/V1/UpdateBabyRequest.php
git rm app/Actions/Baby/ListBabies.php
git rm app/Actions/Baby/UpdateBaby.php
git rm app/Actions/Baby/DeleteBaby.php
```

- [ ] **Step 2: Run full test suite**

Run: `vendor/bin/sail artisan test --compact`
Expected: ALL PASS

- [ ] **Step 3: Run Pint on all changed files**

Run: `vendor/bin/sail bin pint --dirty --format agent`

- [ ] **Step 4: Commit**

```bash
git commit -m "chore: remove unused BabyController, UpdateBabyRequest, and Baby actions"
```
