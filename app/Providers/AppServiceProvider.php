<?php

declare(strict_types=1);

namespace App\Providers;

use App\Support\LimitOffsetPaginator;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;
use Illuminate\Validation\Rules\Password;

final class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        $this->bootModelsDefaults();
        $this->bootPasswordDefaults();
        $this->bootRateLimiting();
        $this->bootPagination();
    }

    private function bootModelsDefaults(): void
    {
        Model::unguard();
    }

    private function bootPasswordDefaults(): void
    {
        Password::defaults(fn () => app()->isLocal() || app()->runningUnitTests() ? Password::min(12)->max(255) : Password::min(12)->max(255)->uncompromised());
    }

    private function bootRateLimiting(): void
    {
        RateLimiter::for('api', fn (Request $request): Limit => $request->user()
            ? Limit::perMinute(600)->by($request->user()->id)
            : Limit::perMinute(100)->by($request->ip()));
    }

    private function bootPagination(): void
    {
        Builder::macro('limitOffsetPaginate', function (int $defaultLimit = 20): LimitOffsetPaginator {
            /** @var Builder $this */
            $limit = request()->integer('limit', $defaultLimit);
            $offset = request()->integer('offset', 0);

            return new LimitOffsetPaginator(
                data: (clone $this)->limit($limit)->offset($offset)->get(),
                total: (clone $this)->count(),
                limit: $limit,
                offset: $offset,
            );
        });
    }
}
