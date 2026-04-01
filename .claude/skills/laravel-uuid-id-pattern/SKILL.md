---
name: laravel-uuid-id-pattern
description: Use when working with Laravel models that expose UUIDs externally (API responses, form requests, route binding) while using integer IDs internally (foreign keys, relations). Covers optimistic update constraints, withAggregate for related UUIDs, and action/controller responsibilities.
---

# Laravel UUID External / ID Internal Pattern

## Overview

UUID for external surface (API, routes, forms). Integer ID for internal surface (FK, Eloquent relations). Never cross the boundary.

## Pattern Rules

| Layer | Use |
|---|---|
| Route binding | `getRouteKeyName(): 'uuid'` |
| Form Request | Client UUID for new resource (stored as-is); related model ID via `resolve()` on route-bound model |
| Resource | Return `uuid` as `id`, related model UUIDs via `withAggregate` |
| Eloquent relations | Always use integer `id` / `*_id` FK |
| Action queries | Explicit `withAggregate('relation', 'uuid')` |

## Form Request — UUID Handling

**Two distinct patterns — don't confuse them.**

### 1. Client-generated UUID (optimistic update)

The client generates a UUID for the new resource before the server responds, enabling optimistic UI updates. Accept and validate it:

```php
// Client sends its own UUID alongside the payload
public function rules(): array
{
    return [
        'uuid' => ['sometimes', 'uuid', 'unique:milk_measures,uuid'],
        'value' => ['required', 'integer', 'min:1'],
        'measured_at' => ['required', 'date_format:Y-m-d\TH:i:s\Z', 'before_or_equal:now'],
    ];
}
```

The UUID is stored as-is on the new model. Never use it as a FK or pass it to relations.

### 2. Related model — resolve via route binding, not form field

If a rule needs the related model's internal ID, retrieve the route-bound model via `resolve()` — no `prepareForValidation` needed:

```php
// Route: /api/v1/babies/{baby:uuid}/milk-goals
public function rules(): array
{
    /** @var Baby $baby */
    $baby = resolve(Baby::class); // already resolved from route by uuid

    return [
        'date' => ['required', 'date_format:Y-m-d', Rule::unique('milk_goals')->where('baby_id', $baby->id)],
        'goal'  => ['required', 'integer', 'min:1'],
    ];
}
```

### 3. Auth user ID injection

`prepareForValidation` is used to inject the authenticated user's internal ID, not for UUID resolution:

```php
public function prepareForValidation(): void
{
    /** @var User $user */
    $user = $this->user();
    $this->merge(['user_id' => $user->id]);
}
```

## Related Model UUID in Resources

Never load a full relation just to get its UUID:

```php
// ❌ Forces eager load of full Category model
'category_id' => $this->category->uuid,

// ✅ Single subquery, no relation hydrated
'category_id' => $this->category_uuid, // from withAggregate
```

Add `withAggregate` explicitly in every action that feeds a resource:

```php
Achievement::query()
    ->withAggregate('category', 'uuid') // adds category_uuid attribute
    ->orderBy('expected_age_min_months')
    ->get();
```

## Constraints — Where `withAggregate` Is NOT Applied

`withAggregate` only runs on SELECT queries. These cases require special handling:

| Case | Why missing | Fix |
|---|---|---|
| `Model::create()` / `$relation->create()` | INSERT, no SELECT | `setAttribute('rel_uuid', $parent->uuid)` in controller (parent already bound) |
| `$model->refresh()` | Uses `newQueryWithoutScopes()` | Replace with `Model::query()->withAggregate(...)->findOrFail($id)` |
| `$model->update()` then return same instance | No re-fetch | Replace with `Model::query()->withAggregate(...)->findOrFail($id)` |
| Route-bound model (show/update endpoints) | Framework resolves before action | Action does fresh `findOrFail` with `withAggregate`, OR controller uses `setAttribute` if parent UUID is available |

## Do Not Use Global Scopes for This

`booted()` + `addGlobalScope` seems convenient but silently breaks on `create()`, `refresh()`, and raw queries — causing `MissingAttributeException` at runtime with no static warning. Keep `withAggregate` explicit in each action.

## PHPDoc

Since `withAggregate` adds a dynamic attribute, declare it on the model so Larastan doesn't report `property.notFound`:

```php
/** @property-read string $category_uuid */
```

## Responsibility Split

- **Action**: owns `withAggregate` for list/show/create/update queries it controls
- **Controller**: uses `setAttribute` only when parent model is already route-bound and no SELECT is possible (e.g. after `create()`)
- **Resource**: reads `$this->relation_uuid` — never accesses `$this->relation->uuid`
