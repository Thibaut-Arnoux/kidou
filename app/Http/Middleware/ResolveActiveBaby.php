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
