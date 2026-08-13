<?php

namespace App\Providers;

use App\Repositories\Contracts\AreaRepository;
use App\Repositories\Contracts\CommunicationRepository;
use App\Repositories\Contracts\NumberCounterRepository;
use App\Repositories\Contracts\PositionRepository;
use App\Repositories\Contracts\RoleRepository;
use App\Repositories\Contracts\SettingsRepository;
use App\Repositories\Contracts\UserRepository;
use App\Repositories\Eloquent\EloquentAreaRepository;
use App\Repositories\Eloquent\EloquentCommunicationRepository;
use App\Repositories\Eloquent\EloquentNumberCounterRepository;
use App\Repositories\Eloquent\EloquentPositionRepository;
use App\Repositories\Eloquent\EloquentRoleRepository;
use App\Repositories\Eloquent\EloquentSettingsRepository;
use App\Repositories\Eloquent\EloquentUserRepository;
use Illuminate\Support\ServiceProvider;

class RepositoryServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        $this->app->bind(UserRepository::class, EloquentUserRepository::class);
        $this->app->bind(AreaRepository::class, EloquentAreaRepository::class);
        $this->app->bind(CommunicationRepository::class, EloquentCommunicationRepository::class);
        $this->app->bind(NumberCounterRepository::class, EloquentNumberCounterRepository::class);
        $this->app->bind(PositionRepository::class, EloquentPositionRepository::class);
        $this->app->bind(SettingsRepository::class, EloquentSettingsRepository::class);
        $this->app->bind(RoleRepository::class, EloquentRoleRepository::class);
    }
}
