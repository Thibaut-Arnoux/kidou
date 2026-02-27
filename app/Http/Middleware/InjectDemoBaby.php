<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Models\Baby;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

final readonly class InjectDemoBaby
{
    /**
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $baby = Baby::query()->firstOrFail();

        app()->instance(Baby::class, $baby);

        return $next($request);
    }
}
