# Result Pattern

The Result class represents the outcome of a domain operation — either success with a value, or failure with an error. It replaces try/catch for expected domain failures, making error handling explicit and visible at the call site.

## The Result Class

Place this in `app/Support/Result.php`:

```php
<?php

namespace App\Support;

final class Result
{
    private function __construct(
        private readonly bool $ok,
        private readonly mixed $val = null,
        private readonly mixed $err = null,
    ) {}

    public static function ok(mixed $value = null): static
    {
        return new static(true, $value);
    }

    public static function err(mixed $error): static
    {
        return new static(false, null, $error);
    }

    public function isOk(): bool
    {
        return $this->ok;
    }

    public function isErr(): bool
    {
        return !$this->ok;
    }

    public function value(): mixed
    {
        return $this->val;
    }

    public function error(): mixed
    {
        return $this->err;
    }
}
```

## Using Result in Actions

An Action that can fail for expected reasons returns `Result` instead of the model directly:

```php
final readonly class CancelOrder
{
    public function handle(Order $order, string $reason): Result
    {
        if ($order->status === 'shipped') {
            return Result::err('This order has already shipped.');
        }

        if ($order->status === 'cancelled') {
            return Result::err('This order is already cancelled.');
        }

        $order->update([
            'status' => 'cancelled',
            'cancelled_reason' => $reason,
        ]);

        return Result::ok($order);
    }
}
```

## Handling Results in Controllers

The controller checks the Result and maps it to an HTTP response. The domain logic stays in the Action — the controller just orchestrates:

```php
public function __invoke(
    CancelOrderRequest $request,
    Order $order,
    CancelOrder $action,
): RedirectResponse {
    $result = $action->handle($order, $request->reason);

    if ($result->isErr()) {
        return back()->withErrors([
            'order' => $result->error(),
        ]);
    }

    return redirect()->route('orders.show', $result->value());
}
```

For API controllers:

```php
public function __invoke(
    CancelOrderRequest $request,
    Order $order,
    CancelOrder $action,
): JsonResponse {
    $result = $action->handle($order, $request->reason);

    if ($result->isErr()) {
        return response()->json([
            'message' => $result->error(),
        ], 422);
    }

    return new JsonResponse(
        new OrderResource($result->value()),
    );
}
```

## Testing Results

Results are straightforward to test — no exception catching needed:

```php
it('cannot cancel a shipped order', function (): void {
    $order = Order::factory()->create(['status' => 'shipped']);
    $action = new CancelOrder();

    $result = $action->handle($order, 'Changed my mind');

    expect($result->isErr())->toBeTrue();
    expect($result->error())->toBe('This order has already shipped.');
});

it('cancels an order successfully', function (): void {
    $order = Order::factory()->create(['status' => 'pending']);
    $action = new CancelOrder();

    $result = $action->handle($order, 'Changed my mind');

    expect($result->isOk())->toBeTrue();
    expect($result->value()->status)->toBe('cancelled');
});
```

## When NOT to Use Result

- If the Action always succeeds (a simple create/update with no business rules), return the model directly. Not every Action needs a Result wrapper.
- If the failure is truly exceptional (database connection lost, external API down), let the exception propagate — that's what exceptions are for.
