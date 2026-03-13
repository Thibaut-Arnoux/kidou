---
name: laravel-validation-architecture
description: "Guides where and how to validate data in Laravel using the Action pattern with Result objects and custom exceptions. Activates when creating or modifying Actions, adding validation logic, handling domain errors, creating Form Requests, deciding between exceptions and return values, implementing business rule checks, or when the user mentions validation, Result pattern, domain errors, action classes, or business rules. Use this skill whenever writing or reviewing code that involves data validation placement or error handling strategy."
---

# Laravel Validation Architecture

This skill defines where validation belongs and how to handle errors across the request lifecycle. The core principle: **validation ensures data correctness, actions enforce business rules, controllers only orchestrate.**

## The Validation Boundary

```
Form Request (validation layer)  → Is the data correct?
Action class (domain layer)      → Is the operation allowed?
Controller                       → Orchestration only
```

These two concerns look similar but serve different purposes. A Form Request rejects malformed input before it reaches domain logic. An Action rejects operations that violate business rules even when the input is perfectly valid.

## The Controller's Role: Orchestration Only

A controller's job is to wire things together: receive a validated request, call the action, and translate the result into an HTTP response. Nothing else.

If you see any of the following in a controller, it's a smell — the logic belongs somewhere else:

- **`$request->validate([...])`** → Move to a Form Request. Inline validation clutters the controller and can't be reused.
- **`abort_if` / `abort_unless` with a business rule** → Move to the Action and return a `Result`. The controller should check `$result->isErr()` and map it to an HTTP status, not enforce domain rules.
- **Model lookups beyond route-model binding** (e.g., `Model::where(...)->firstOrFail()`) → Consider resolving in the Form Request or via route-model binding. The controller should receive ready-to-use objects.
- **Conditional logic that decides whether the operation can proceed** → That's a business rule. It belongs in the Action.

A clean controller method reads like a pipeline: request in → action called → response out. When reviewing code, check controllers first — if they're doing more than orchestrating, something is in the wrong layer.

## The Decision Tree

Every check in your code goes through two levels of decision. Follow them in order.

### Level 1: Form Request or Action?

Ask yourself: **does this check depend on the current state of the domain, or only on the incoming data?**

- If it's about the **shape, format, or validity of input data** → Form Request (questions 1–5 below)
- If it's about whether the **operation is allowed given the current state of the system** → Action (question 6 below)

This separation matters because Actions get called from controllers, queued jobs, CLI commands, and other Actions — they should never assume an HTTP request exists. Input validation is the HTTP boundary's job. Business rules are the domain's job.

### Level 2a: Which Form Request hook?

If the check belongs in the Form Request, pick the right hook by asking these questions in order:

1. **Is the data dirty?** → `prepareForValidation()` — normalize/clean input before any rules run (trim whitespace, format phone numbers, merge defaults). This is not validation — it's preparation.

2. **Is a single field invalid on its own?** → `rules()` — the standard place for field-level constraints (required, type, range, format, unique). Most validation lives here.

3. **Is a field invalid given other fields?** → `DataAwareRule` — a reusable rule class that receives the full input array. Use when the validity of one field depends on another field's value, and you want to reuse that logic across multiple Form Requests.

4. **Do multiple fields conflict with each other?** → `after()` hook — runs after all individual rules pass. Use for cross-field consistency checks where the question is "do these validated fields make sense together?" (e.g., start_date < end_date, conflicting options).

5. **Is the user allowed?** → `authorize()` — ownership checks, permission checks, "does this resource belong to the current user?" This runs before validation rules.

### Level 2b: Result or Exception?

If the check belongs in the Action, pick the right error mechanism:

**Use Result when the failure is expected** — the system is working correctly, the operation just can't proceed. The caller is expected to handle this gracefully and show a meaningful message to the user.

Examples: order already cancelled, insufficient balance, user exceeded daily limit, duplicate entry, invalid state transition (can't ship a draft order), resource has dependent records that prevent deletion.

**Use Exceptions when something has genuinely gone wrong** — an exceptional situation that shouldn't happen during normal operation.

Examples: database connection lost, third-party API unreachable, payment gateway returns unexpected error, data corruption detected, impossible state reached.

**The litmus test:** If a user's normal workflow can trigger this failure, it's a Result. If it means something broke, it's an Exception.

Both coexist naturally — an Action returns `Result` for its domain checks while letting infrastructure exceptions propagate through Laravel's exception handler.

Read `references/result-pattern.md` for the Result class implementation and usage examples.
Read `references/exception-design.md` for custom exception patterns.

### Quick Reference

```
Check to add
│
├─ About input data? ──────────── FORM REQUEST
│   ├─ Dirty/messy input?        → prepareForValidation()
│   ├─ Single field invalid?     → rules()
│   ├─ Field invalid given others? → DataAwareRule
│   ├─ Fields conflict together? → after()
│   └─ User not allowed?         → authorize()
│
└─ About domain state? ────────── ACTION
    ├─ Expected failure?         → return Result::err()
    └─ Something broke?          → throw Exception
```

## Form Request Advanced Patterns

### prepareForValidation()

Clean incoming data before validation runs. Use this for normalization, not business logic:

```php
protected function prepareForValidation(): void
{
    $this->merge([
        'phone' => preg_replace('/[^0-9+]/', '', $this->phone),
    ]);
}
```

### after() Hook

Runs after all field-level rules pass. Use for multi-field conflict detection — questions like "do these fields contradict each other?"

```php
public function after(): array
{
    return [
        function (Validator $validator): void {
            if ($this->start_date >= $this->end_date) {
                $validator->errors()->add(
                    'end_date',
                    'End date must be after start date.'
                );
            }
        },
    ];
}
```

### DataAwareRule

A reusable validation rule that needs context from other fields. Use when the question is "is this field valid given the other fields?" — and you want to reuse that check across multiple Form Requests.

```php
class UniqueForTenant implements ValidationRule, DataAwareRule
{
    protected array $data = [];

    public function setData(array $data): static
    {
        $this->data = $data;
        return $this;
    }

    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        $exists = Tenant::find($this->data['tenant_id'])
            ->users()
            ->where('email', $value)
            ->exists();

        if ($exists) {
            $fail('This email is already registered for this tenant.');
        }
    }
}
```

## File Placement

Following the project's existing conventions:

| What | Where |
|------|-------|
| Result class | `app/Support/Result.php` |
| Action classes | `app/Actions/{Domain}/` |
| Custom exceptions | `app/Exceptions/{Domain}/` |
| Form Requests | `app/Http/Requests/` (or `app/Http/Requests/Api/V1/`) |
| Custom validation rules | `app/Rules/` |
