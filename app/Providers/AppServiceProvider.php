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

        $this->app->bind(NotificationRepositoryInterface::class, NotificationRepository::class);

        $this->app->bind(AdminRepositoryInterface::class, AdminRepository::class);

        $this->app->bind(PermissionRepositoryInterface::class, PermissionRepository::class);
        $this->app->bind(RoleRepositoryInterface::class, RoleRepository::class);
        $this->app->bind(BaseRepositoryInterface::class, BaseRepository::class);
        $this->app->bind(UserRepositoryInterface::class, UserRepository::class);
        $this->app->bind(MediaRepositoryInterface::class, MediaRepository::class);

        // Car marketplace bindings
        $this->app->bind(DealerRepositoryInterface::class, DealerRepository::class);
        $this->app->bind(ListingRepositoryInterface::class, ListingRepository::class);
        $this->app->bind(ListingEventRepositoryInterface::class, ListingEventRepository::class);
        $this->app->singleton('firebase-notification', function ($app) {
            $projectId = config('firebase.project_id');
            $credentialsFilePath = config('firebase.credentials_file_path');
            AccessToken::initialize($credentialsFilePath, $projectId);
            return new FirebaseNotification($projectId, $credentialsFilePath);
        });
    }

}
