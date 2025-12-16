<?php

namespace Apiato\Core\Providers;

use Apiato\Core\Abstracts\Providers\MainServiceProvider as AbstractMainServiceProvider;
use Apiato\Core\Criterias\RequestCriteria;
use Apiato\Core\Foundation\Apiato;
use Apiato\Core\Http\ApiResponse;
use Apiato\Core\Loaders\AutoLoaderTrait;
use Illuminate\Support\Facades\Schema;
use Prettus\Repository\Criteria\RequestCriteria as ParentRequestCriteria;

class ApiatoServiceProvider extends AbstractMainServiceProvider
{
    use AutoLoaderTrait;

    public array $serviceProviders = [
    ];

    public function register(): void
    {
        // NOTE: function order of this calls bellow are important. Do not change it.

        $this->app->bind('Apiato', Apiato::class);
        $this->app->bind('ApiResponse', ApiResponse::class);
        // Register Core Facade Classes, should not be registered in the $aliases property, since they are used
        // by the auto-loading scripts, before the $aliases property is executed.
        $this->app->alias(\Apiato\Core\Foundation\Facades\ApiResponse::class, 'ApiResponse');

        // parent::register() should be called AFTER we bind 'Apiato'
        parent::register();

        $this->runLoaderRegister();

        // Replace Prettus RequestCriteria
        $this->app->bind(ParentRequestCriteria::class, RequestCriteria::class);
    }

    public function boot(): void
    {
        parent::boot();

        // Autoload most of the Containers and Ship Components
        $this->runLoadersBoot();

        // Solves the "specified key was too long" error, introduced in L5.4
        Schema::defaultStringLength(191);
    }
}
