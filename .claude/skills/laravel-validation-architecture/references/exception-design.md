# Custom Exception Design

Custom exceptions represent truly exceptional domain failures — situations that shouldn't happen during normal operation but need to be handled gracefully when they do. Use them for infrastructure problems, unrecoverable errors, and cases where the Action cannot return a meaningful Result.

## When to Use Exceptions Instead of Result

The rule of thumb: **if the caller is expected to handle this failure as part of normal flow, use Result. If something has genuinely gone wrong, throw an exception.**

Concrete examples where exceptions are appropriate:

- A third-party payment gateway returns an unexpected error
- A required external service is unreachable
- Data integrity is compromised (an order references a deleted product)
- An operation that "should never fail" fails (e.g., a refund for a completed order where the payment provider rejects it)

## Designing Domain Exceptions

A good domain exception carries enough context to be useful for logging, rendering, and debugging. It includes:

1. **The entity involved** — passed via constructor
2. **A human-readable message** — set in the constructor
3. **A `context()` method** — structured data for logging
4. **A `render()` method** — HTTP response for web/API contexts
5. **A `report()` method** — controls logging behavior

### Example: Full Domain Exception

Place domain exceptions in `app/Exceptions/{Domain}/`:

```php
<?php

namespace App\Exceptions\Order;

use App\Models\Order;
use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

final class OrderAlreadyShippedException extends Exception
{
    public function __construct(
        protected Order $order,
    ) {
        parent::__construct(
            "Order {$order->id} has already shipped."
        );
    }

    /**
     * Structured context for logging and monitoring.
     */
    public function context(): array
    {
        return [
            'order_id' => $this->order->id,
            'status' => $this->order->status,
            'shipped_at' => $this->order->shipped_at,
        ];
    }

    /**
     * Render the exception as an HTTP response.
     */
    public function render(Request $request): JsonResponse|RedirectResponse
    {
        if ($request->expectsJson()) {
            return response()->json([
                'message' => $this->getMessage(),
                'order_id' => $this->order->id,
            ], 409);
        }

        return back()->with(
            'error',
            'This order has already been shipped and cannot be cancelled.'
        );
    }

    /**
     * Control how this exception is reported/logged.
     * Return false to prevent default logging (useful when you handle it yourself).
     */
    public function report(): bool
    {
        Log::info(
            'Cancellation attempted on shipped order',
            $this->context()
        );

        return false;
    }
}
```

### Naming Convention

Name exceptions after the violation, not the action. The exception describes what went wrong:

- `OrderAlreadyShippedException` (not `CancelShippedOrderException`)
- `OrderNotCompletedException` (not `RefundIncompleteOrderException`)
- `RefundWindowExpiredException` (not `LateRefundException`)

This makes exceptions reusable — `OrderAlreadyShippedException` can be thrown from any action that needs a non-shipped order, not just cancellation.

## Using Exceptions in Actions

When an Action uses exceptions, its return type is the success value directly (no Result wrapper). Multiple validation checks can each throw their own specific exception:

```php
final readonly class RefundOrder
{
    public function handle(Order $order): void
    {
        if ($order->status === 'refunded') {
            throw new OrderAlreadyRefundedException($order);
        }

        if ($order->status !== 'completed') {
            throw new OrderNotCompletedException($order);
        }

        if ($order->completed_at < now()->subDays(30)) {
            throw new RefundWindowExpiredException($order, days: 30);
        }

        $order->update(['status' => 'refunded']);

        RefundPayment::dispatch($order);
    }
}
```

## Choosing Between Result and Exception in the Same Domain

Most actions should use the Result pattern for domain failures. Reserve exceptions for the rare cases described above. When in doubt, prefer Result — it keeps error handling explicit and local.

If an action already returns `Result` for its domain checks, infrastructure exceptions can still propagate naturally through Laravel's exception handler. The two patterns coexist cleanly.
