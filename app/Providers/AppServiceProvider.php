<?php

namespace App\Providers;


use App\Repositories\Interfaces\NotificationRepositoryInterface;
use App\Repositories\Eloquent\NotificationRepository;


use App\Repositories\Interfaces\AdminRepositoryInterface;
use App\Repositories\Eloquent\AdminRepository;
use App\Libraries\Firebase\AccessToken;
use App\Libraries\Firebase\FirebaseNotification;
use App\Repositories\Interfaces\PermissionRepositoryInterface;
use App\Repositories\Eloquent\PermissionRepository;
use App\Repositories\Interfaces\RoleRepositoryInterface;
use App\Repositories\Eloquent\RoleRepository;
use App\Repositories\Eloquent\BaseRepository;
use App\Repositories\Interfaces\BaseRepositoryInterface;
use App\Repositories\Eloquent\MediaRepository;
use App\Repositories\Interfaces\UserRepositoryInterface;
use App\Repositories\Eloquent\UserRepository;
use App\Repositories\Interfaces\MediaRepositoryInterface;

// Car marketplace repositories
use App\Repositories\Interfaces\DealerRepositoryInterface;
use App\Repositories\Eloquent\DealerRepository;
use App\Repositories\Interfaces\ListingRepositoryInterface;
use App\Repositories\Eloquent\ListingRepository;
use App\Repositories\Interfaces\ListingEventRepositoryInterface;
use App\Repositories\Eloquent\ListingEventRepository;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {

        $this->app->bind(BaseRepositoryInterface::class, BaseRepository::class);
        $this->app->bind(UserRepositoryInterface::class, UserRepository::class);

        // Car marketplace bindings
        $this->app->bind(DealerRepositoryInterface::class, DealerRepository::class);
        $this->app->bind(ListingRepositoryInterface::class, ListingRepository::class);
        $this->app->bind(ListingEventRepositoryInterface::class, ListingEventRepository::class);

        // Services
        $this->app->singleton(\App\Services\CacheService::class);
        $this->app->bind(\App\Services\CarService::class);
        $this->app->bind(\App\Services\LeadScoringService::class);

    }

    public function boot(): void
    {
        // Register model observers
        \App\Models\Listing::observe(\App\Observers\ListingObserver::class);

        // Configure rate limiting
        $this->configureRateLimiting();
    }

    private function configureRateLimiting(): void
    {
        \Illuminate\Support\Facades\RateLimiter::for('leads', function (\Illuminate\Http\Request $request) {
            $email = $request->input('email', '');
            $ip = $request->ip();
            return \Illuminate\Cache\RateLimiting\Limit::perHour(5)
                ->by("leads:{$ip}:{$email}")
                ->response(function () {
                    return response()->json([
                        'message' => 'Too many lead submissions. Please try again later.',
                        'error' => 'rate_limit_exceeded'
                    ], 429);
                });
        });

        \Illuminate\Support\Facades\RateLimiter::for('api', function (\Illuminate\Http\Request $request) {
            return \Illuminate\Cache\RateLimiting\Limit::perMinute(60)->by($request->ip());
        });
    }

}
