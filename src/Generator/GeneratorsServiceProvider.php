<?php

namespace Apiato\Core\Generator;

use Apiato\Core\Generator\Commands\ActionGenerator;
use Apiato\Core\Generator\Commands\CommandGenerator;
use Apiato\Core\Generator\Commands\ConfigurationGenerator;
use Apiato\Core\Generator\Commands\ContainerApiGenerator;
use Apiato\Core\Generator\Commands\HttpRequestGenerator;
use Apiato\Core\Generator\Commands\ControllerGenerator;
use Apiato\Core\Generator\Commands\DTOGenerator;
use Apiato\Core\Generator\Commands\JobGenerator;
use Apiato\Core\Generator\Commands\MigrationGenerator;
use Apiato\Core\Generator\Commands\ModelFactoryGenerator;
use Apiato\Core\Generator\Commands\ModelGenerator;
use Apiato\Core\Generator\Commands\RepositoryGenerator;
use Apiato\Core\Generator\Commands\RequestGenerator;
use Apiato\Core\Generator\Commands\RouteGenerator;
use Apiato\Core\Generator\Commands\ServiceProviderGenerator;
use Apiato\Core\Generator\Commands\TaskGenerator;
use Apiato\Core\Generator\Commands\TransformerGenerator;
use Illuminate\Support\ServiceProvider;

class GeneratorsServiceProvider extends ServiceProvider
{
    /**
     * Bootstrap the application services.
     */
    public function boot()
    {
        if ($this->app->runningInConsole()) {
            $this->commands($this->getGeneratorCommands());
        }
    }

    private function getGeneratorCommands(): array
    {
        // add your generators here
        return [
            ActionGenerator::class,
            ConfigurationGenerator::class,
            CommandGenerator::class,
            ContainerApiGenerator::class,
            ControllerGenerator::class,
            DTOGenerator::class,
            JobGenerator::class,
            ModelFactoryGenerator::class,
            MigrationGenerator::class,
            ModelGenerator::class,
            RepositoryGenerator::class,
            RequestGenerator::class,
            RouteGenerator::class,
            ServiceProviderGenerator::class,
            TaskGenerator::class,
            TransformerGenerator::class,
            HttpRequestGenerator::class,
        ];
    }
}
