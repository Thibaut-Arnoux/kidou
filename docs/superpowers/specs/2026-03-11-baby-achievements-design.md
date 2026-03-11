# Baby Achievements Feature — Design Spec

## Overview

A gamification feature for Kidou that tracks developmental milestones for babies from birth to 36 months. Predefined achievements are organized by developmental category. Parents can link/unlink achievements to their baby with a specific datetime and optional note. Parents can also create custom achievements in a dedicated "Custom" category.

## Database Schema

### `categories` table

| Column | Type | Notes |
|--------|------|-------|
| id | bigint (PK, auto-increment) | |
| uuid | uuid (unique) | Route key |
| name | varchar(255) | e.g. "Motor Skills" |
| slug | varchar(255, unique) | e.g. "motor-skills" |
| is_custom | boolean (default: false) | `true` only for "Custom" |
| created_at | timestamp | |
| updated_at | timestamp | |

### `achievements` table

| Column | Type | Notes |
|--------|------|-------|
| id | bigint (PK, auto-increment) | |
| uuid | uuid (unique) | Route key |
| category_id | bigint (FK -> categories.id, cascade) | |
| user_id | bigint (FK -> users.id, cascade, nullable) | `null` = predefined, set = custom |
| name | varchar(255) | |
| description | text (nullable) | Brief explanation |
| expected_age_min_months | unsigned integer (nullable) | Age indicator start |
| expected_age_max_months | unsigned integer (nullable) | Age indicator end |
| created_at | timestamp | |
| updated_at | timestamp | |

### `baby_achievement` pivot table

| Column | Type | Notes |
|--------|------|-------|
| id | bigint (PK, auto-increment) | |
| uuid | uuid (unique) | Route key |
| baby_id | bigint (FK -> babies.id, cascade) | |
| achievement_id | bigint (FK -> achievements.id, cascade) | |
| achieved_at | datetime | When the baby achieved it |
| note | text (nullable) | Optional parent note |
| created_at | timestamp | |
| updated_at | timestamp | |
| **unique(baby_id, achievement_id)** | | One link per baby per achievement |

## Models & Relationships

### Category (`final class`)

- `#[ObservedBy(CategoryObserver::class)]`
- `hasMany(Achievement)`
- Uses UUID as route key
- `$fillable`: `name`, `slug`, `is_custom`
- Slug is set manually via seeders (no auto-generation needed since categories are not user-created)

### Achievement (`final class`)

- `#[ObservedBy(AchievementObserver::class)]`
- `belongsTo(Category)`
- `belongsTo(User)` (nullable — only for custom achievements)
- `belongsToMany(Baby)` via `baby_achievement` pivot, using `BabyAchievement` custom pivot model
- Uses UUID as route key
- `$fillable`: `category_id`, `user_id`, `name`, `description`, `expected_age_min_months`, `expected_age_max_months`
- Scope `predefined()` — where `user_id` is null
- Scope `customForUser(User $user)` — where `user_id` = user id

### BabyAchievement (`final class`, custom Pivot model)

- `#[ObservedBy(BabyAchievementObserver::class)]`
- Extends `Illuminate\Database\Eloquent\Relations\Pivot`
- `$incrementing = true`
- Casts: `achieved_at` as `datetime`
- `$fillable`: `baby_id`, `achievement_id`, `achieved_at`, `note`

### Baby (existing model — new relationship)

```php
public function achievements(): BelongsToMany
{
    return $this->belongsToMany(Achievement::class)
        ->using(BabyAchievement::class)
        ->as('link')
        ->withPivot('uuid', 'achieved_at', 'note')
        ->withTimestamps();
}
```

## API Endpoints

All endpoints use the existing `InjectDemoBaby` middleware pattern under `/v1`.

### Categories

| Method | Route | Controller | Description |
|--------|-------|------------|-------------|
| GET | `/v1/categories` | `CategoryController@index` | List all categories with progress meta |

**Response:**

```json
{
    "data": [
        {
            "id": "...",
            "name": "Motor Skills",
            "slug": "motor-skills",
            "is_custom": false,
            "total_achievements": 17,
            "completed_achievements": 5
        }
    ]
}
```

### Category Achievements

| Method | Route | Controller | Description |
|--------|-------|------------|-------------|
| GET | `/v1/categories/{category}/achievements` | `CategoryAchievementController@index` | List achievements for a category with link status |

**Response:**

```json
{
    "data": [
        {
            "id": "...",
            "name": "Head Control",
            "description": "Holds head steady and upright when held in a sitting position.",
            "expected_age_min_months": 1,
            "expected_age_max_months": 4,
            "link": {
                "id": "...",
                "achieved_at": "2026-02-15T14:30:00Z",
                "note": "During tummy time!"
            }
        },
        {
            "id": "...",
            "name": "Rolls Over",
            "description": "Rolls from tummy to back and back to tummy.",
            "expected_age_min_months": 3,
            "expected_age_max_months": 6,
            "link": null
        }
    ]
}
```

### Custom Achievements

| Method | Route | Controller | Description |
|--------|-------|------------|-------------|
| POST | `/v1/achievements` | `AchievementController@store` | Create custom achievement (Custom category only) |
| DELETE | `/v1/achievements/{achievement}` | `AchievementController@destroy` | Delete custom achievement (custom only) |

**POST body:**

```json
{
    "name": "First time at the park",
    "description": "Optional description",
    "expected_age_min_months": null,
    "expected_age_max_months": null
}
```

### Achievement Linking

| Method | Route | Controller | Description |
|--------|-------|------------|-------------|
| POST | `/v1/achievements/{achievement}/link` | `AchievementLinkController@store` | Link achievement to baby |
| PUT | `/v1/achievements/{achievement}/link` | `AchievementLinkController@update` | Update link (achieved_at, note) |
| DELETE | `/v1/achievements/{achievement}/link` | `AchievementLinkController@destroy` | Unlink achievement from baby |

**POST/PUT body:**

```json
{
    "achieved_at": "2026-02-15T14:30:00",
    "note": "During tummy time!"
}
```

## Validation Rules

### StoreAchievementRequest (custom achievement creation)

- `uuid`: sometimes, uuid, unique:achievements
- `name`: required, string, max:255
- `description`: nullable, string
- `expected_age_min_months`: nullable, integer, min:0, max:36
- `expected_age_max_months`: nullable, integer, min:0, max:36, gte:expected_age_min_months

### StoreAchievementLinkRequest (linking)

- `uuid`: sometimes, uuid, unique:baby_achievement
- `achieved_at`: required, date
- `note`: nullable, string

### UpdateAchievementLinkRequest (update link)

- `achieved_at`: sometimes, required, date
- `note`: nullable, string

## Business Rules

1. Only achievements with `user_id` set (custom) can be deleted
2. Predefined achievements (`user_id = null`) cannot be deleted
3. Custom achievements are always assigned to the "Custom" category (is_custom = true)
4. A baby can only link an achievement once (unique constraint on baby_id + achievement_id)
5. For non-custom categories: list only predefined achievements (`user_id = null`)
6. For the Custom category: list only achievements where `user_id` matches the current user
7. When listing categories, `total_achievements` and `completed_achievements` are computed relative to the current baby

## Seeders

### CategorySeeder

Seeds 6 categories:

1. Motor Skills (slug: motor-skills)
2. Language (slug: language)
3. Social & Emotional (slug: social-emotional)
4. Cognitive (slug: cognitive)
5. Self-Care (slug: self-care)
6. Custom (slug: custom, is_custom: true)

### AchievementSeeder

Seeds 56 predefined achievements (user_id = null):

#### Motor Skills (17 milestones)

| Name | Description | Min | Max |
|------|-------------|-----|-----|
| Head Control | Holds head steady and upright when held in a sitting position | 1 | 4 |
| Pushes Up on Arms | Lifts head and chest off the floor while lying on tummy | 2 | 4 |
| Rolls Over | Rolls from tummy to back and back to tummy | 3 | 6 |
| Sits Without Support | Sits steadily on their own without needing to prop on hands | 5 | 8 |
| Raking Grasp | Uses fingers to rake small objects toward themselves | 5 | 8 |
| Crawls | Moves forward on hands and knees in a coordinated way | 6 | 10 |
| Pulls to Stand | Pulls themselves up to a standing position using furniture | 7 | 12 |
| Pincer Grasp | Picks up small objects between thumb and forefinger | 8 | 12 |
| Cruises Along Furniture | Walks sideways while holding onto furniture for support | 8 | 13 |
| First Steps Independently | Takes several steps without holding onto anything | 9 | 15 |
| Stacks Two Blocks | Places one block on top of another deliberately | 11 | 16 |
| Walks Steadily | Walks with a stable, coordinated gait and rarely falls | 12 | 18 |
| Kicks a Ball | Kicks a ball forward with one foot while standing | 18 | 24 |
| Runs | Runs with a fairly coordinated gait | 18 | 26 |
| Climbs Stairs with Help | Walks up stairs while holding a railing or an adult's hand | 18 | 26 |
| Jumps with Both Feet | Jumps off the ground with both feet leaving the floor | 24 | 36 |
| Pedals a Tricycle | Pushes pedals on a tricycle to move forward | 28 | 36 |

#### Language (11 milestones)

| Name | Description | Min | Max |
|------|-------------|-----|-----|
| Cooing | Produces soft vowel-like sounds in response to voices | 1 | 4 |
| Babbling | Repeats consonant-vowel combinations like "bababa" or "mamama" | 4 | 8 |
| Responds to Own Name | Turns head or looks toward the speaker when their name is called | 5 | 9 |
| First Word | Says one or two recognizable words with meaning | 9 | 14 |
| Points to Show or Request | Points at objects to express interest or ask for something | 9 | 14 |
| Follows Simple Instructions | Understands and carries out a one-step request | 10 | 16 |
| Uses 10+ Words | Has a spoken vocabulary of at least ten distinct words | 14 | 20 |
| Two-Word Phrases | Combines two words to form simple phrases like "more milk" | 18 | 24 |
| Names Familiar Objects | Can label common objects when asked "what's this?" | 18 | 26 |
| Uses Short Sentences | Speaks in sentences of three or more words | 24 | 36 |
| Strangers Can Understand Speech | Most of what the child says can be understood by unfamiliar adults | 30 | 36 |

#### Social & Emotional (10 milestones)

| Name | Description | Min | Max |
|------|-------------|-----|-----|
| Social Smile | Smiles in response to a caregiver's face or voice | 1 | 3 |
| Enjoys Social Play | Laughs during peek-a-boo and other simple interactive games | 3 | 6 |
| Stranger Anxiety | Shows wariness or distress around unfamiliar people | 6 | 10 |
| Separation Anxiety | Becomes upset when a primary caregiver leaves the room | 7 | 12 |
| Waves Bye-Bye | Waves hand in a social gesture when someone leaves | 8 | 14 |
| Shows Affection | Hugs, kisses, or cuddles with familiar people spontaneously | 12 | 18 |
| Parallel Play | Plays alongside other children doing similar activities | 18 | 26 |
| Shows Empathy | Notices when another person is upset and may try to comfort them | 18 | 28 |
| Takes Turns in Simple Games | Can wait briefly and alternate turns during a simple game | 24 | 36 |
| Engages in Pretend Play with Others | Participates in make-believe scenarios with peers or caregivers | 28 | 36 |

#### Cognitive (8 milestones)

| Name | Description | Min | Max |
|------|-------------|-----|-----|
| Follows Moving Objects | Tracks a slowly moving object with their eyes | 0 | 3 |
| Explores Objects with Hands and Mouth | Brings objects to mouth and manipulates them | 3 | 6 |
| Object Permanence | Understands that an object still exists even when hidden | 6 | 10 |
| Cause and Effect | Intentionally repeats actions to see results | 6 | 12 |
| Imitates Actions | Copies simple gestures and actions performed by adults | 8 | 14 |
| Simple Shape Sorting | Fits basic shapes into the correct holes on a shape sorter | 18 | 26 |
| Pretend Play | Uses objects symbolically, such as pretending a block is a phone | 18 | 28 |
| Sorts by Color or Shape | Groups objects together based on one attribute | 28 | 36 |

#### Self-Care (10 milestones)

| Name | Description | Min | Max |
|------|-------------|-----|-----|
| Holds Own Bottle | Grasps and holds a bottle to feed independently | 5 | 9 |
| Finger Feeds | Picks up small pieces of food and brings them to mouth | 7 | 10 |
| Drinks from Cup with Help | Takes sips from a cup held or guided by a caregiver | 8 | 14 |
| Uses a Spoon (with spilling) | Attempts to scoop food with a spoon, though messily | 12 | 18 |
| Removes Simple Clothing | Pulls off loose items like socks, shoes, or a hat | 12 | 20 |
| Drinks from Cup Independently | Holds and drinks from a cup without significant spilling | 16 | 24 |
| Uses a Spoon Neatly | Feeds themselves with a spoon with minimal spilling | 20 | 28 |
| Shows Interest in Toilet Training | Tells caregiver about wet or dirty diaper, or wants to sit on the potty | 18 | 30 |
| Helps with Dressing | Cooperates by pushing arms through sleeves or stepping into pants | 20 | 30 |
| Washes and Dries Hands with Help | Participates in hand washing with guidance | 24 | 36 |

## File Structure

```
app/
  Actions/
    Category/
      ListCategories.php
    CategoryAchievement/
      ListCategoryAchievements.php
    Achievement/
      CreateAchievement.php
      DeleteAchievement.php
    AchievementLink/
      LinkAchievement.php
      UpdateAchievementLink.php
      UnlinkAchievement.php
  Models/
    Category.php
    Achievement.php
    BabyAchievement.php          (Pivot model)
  Observers/
    AchievementObserver.php
    BabyAchievementObserver.php
    CategoryObserver.php
  Http/
    Controllers/Api/V1/
      CategoryController.php
      CategoryAchievementController.php
      AchievementController.php
      AchievementLinkController.php
    Requests/Api/V1/
      StoreAchievementRequest.php
      StoreAchievementLinkRequest.php
      UpdateAchievementLinkRequest.php
    Resources/
      CategoryResource.php
      AchievementResource.php
database/
  migrations/
    xxxx_create_categories_table.php
    xxxx_create_achievements_table.php
    xxxx_create_baby_achievement_table.php
  seeders/
    CategorySeeder.php
    AchievementSeeder.php
  factories/
    CategoryFactory.php
    AchievementFactory.php
routes/
  api/v1.php                     (add new routes)
```

## Testing Strategy

- Feature tests for each endpoint (CRUD categories, achievements, linking)
- Verify predefined achievements cannot be deleted
- Verify custom achievements scoped to user
- Verify unique constraint on baby_achievement (no duplicate links)
- Verify progress meta counts (total/completed per category)
- Factory-based test data using `hasAttached` for pivot relationships
