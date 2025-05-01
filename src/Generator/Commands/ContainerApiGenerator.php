<?php

namespace Apiato\Core\Generator\Commands;

use Apiato\Core\Generator\GeneratorCommand;
use Apiato\Core\Generator\Interfaces\ComponentsGenerator;
use Illuminate\Support\Pluralizer;
use Illuminate\Support\Str;
use Symfony\Component\Console\Input\InputOption;

class ContainerApiGenerator extends GeneratorCommand implements ComponentsGenerator
{
    /**
     * User required/optional inputs expected to be passed while calling the command.
     * This is a replacement of the `getArguments` function "which reads whenever it's called".
     */
    public array $inputs = [
        ['docversion', null, InputOption::VALUE_OPTIONAL, 'The version of all endpoints to be generated (1, 2, ...)'],
        ['url', null, InputOption::VALUE_OPTIONAL, 'The base URI of all endpoints (/stores, /cars, ...)'],
    ];

    /**
     * The console command name.
     *
     * @var string
     */
    protected $name = 'apiato:generate:container';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Create a Container for apiato from scratch (API Part)';

    /**
     * The type of class being generated.
     */
    protected string $fileType = 'Container';

    /**
     * The structure of the file path.
     */
    protected string $pathStructure = '{section-name}/{container-name}/*';

    /**
     * The structure of the file name.
     */
    protected string $nameStructure = '{file-name}';

    /**
     * The name of the stub file.
     */
    protected string $stubName = 'readme.stub';

    public function getUserInputs(): ?array
    {
        $ui = 'api';

        // section name as inputted and lower
        $sectionName = $this->sectionName;
        $_sectionName = Str::lower($this->sectionName);

        // container name as inputted and lower
        $containerName = $this->containerName;
        $_containerName = Str::lower($this->containerName);

        // name of the model (singular and plural)
        $model = $this->containerName;
        $models = Pluralizer::plural($model);

        // create the configuration file
        $this->printInfoMessage('Generating Configuration File');
        $this->call('apiato:generate:configuration', [
            '--section' => $sectionName,
            '--container' => $containerName,
            '--file' => Str::camel($this->sectionName).'-'.Str::camel($this->containerName),
        ]);

        // create the MainServiceProvider for the container
        $this->printInfoMessage('Generating MainServiceProvider');
        $this->call('apiato:generate:provider', [
            '--section' => $sectionName,
            '--container' => $containerName,
            '--file' => 'MainServiceProvider',
            '--stub' => 'mainserviceprovider',
        ]);

        // create the model for this container
        $this->printInfoMessage('Generating Model');
        $this->call('apiato:generate:model', [
            '--section' => $sectionName,
            '--container' => $containerName,
            '--file' => $model,
        ]);

        $this->printInfoMessage('Generating Repository for the Model');
        $this->call('apiato:generate:repository', [
            '--section' => $sectionName,
            '--container' => $containerName,
            '--file' => $model.'Repository',
        ]);

        // create the migration file for the model
        $this->printInfoMessage('Generating a basic Migration file');
        $this->call('apiato:generate:migration', [
            '--section' => $sectionName,
            '--container' => $containerName,
            '--file' => 'create_'.Str::snake($models).'_table',
            '--tablename' => Str::snake($models),
        ]);

        // create a transformer for the model
        $this->printInfoMessage('Generating Transformer for the Model');
        $this->call('apiato:generate:transformer', [
            '--section' => $sectionName,
            '--container' => $containerName,
            '--file' => $containerName.'Transformer',
            '--model' => $model,
            '--full' => false,
        ]);

        // create a factory for the model
        $this->printInfoMessage('Generating Factory for the Model');
        $this->call('apiato:generate:factory', [
            '--section' => $sectionName,
            '--container' => $containerName,
            '--file' => $containerName.'Factory',
            '--model' => $model,
        ]);

        // create the default routes for this container
        $this->printInfoMessage('Generating Default Routes');
        $version = $this->checkParameterOrAsk('docversion', 'Enter the version for all API endpoints (integer)', 1);

        // get the URI and remove the first trailing slash
        $url = Str::lower($this->checkParameterOrAsk('url', 'Enter the base URI for all API endpoints (foo/bar/{id})', Str::kebab($models)));
        $url = ltrim($url, '/');

        $this->printInfoMessage('Generating Requests for Routes');
        $this->printInfoMessage('Generating Default Actions');
        $this->printInfoMessage('Generating Default Tasks');
        $this->printInfoMessage('Generating Default Controller/s');

        $entity = Str::lower($model);

        $routes = [
            [
                'stub' => 'List',
                'name' => 'List'.$models,
                'operation' => 'list'.$models,
                'verb' => 'GET',
                'url' => $url,
                'action' => 'List'.$models.'Action',
                'request' => 'List'.$models.'Request',
                'task' => 'List'.$models.'Task',
                'dto' => 'List'.$models.'Data',
                'controller' => 'List'.$models.'Controller',
                'request_stub' => 'list',
            ],
            [
                'stub' => 'Find',
                'name' => 'Find'.$model.'ById',
                'operation' => 'find'.$model.'ById',
                'verb' => 'GET',
                'url' => $url.'/{'.$entity.'}',
                'action' => 'Find'.$model.'ByIdAction',
                'request' => 'Find'.$model.'ByIdRequest',
                'task' => 'Find'.$model.'ByIdTask',
                'dto' => 'Find'.$model.'ByIdData',
                'controller' => 'Find'.$model.'ByIdController',
                'request_stub' => 'generic',
            ],
            [
                'stub' => 'Create',
                'name' => 'Create'.$model,
                'operation' => 'create'.$model,
                'verb' => 'POST',
                'url' => $url,
                'action' => 'Create'.$model.'Action',
                'request' => 'Create'.$model.'Request',
                'task' => 'Create'.$model.'Task',
                'dto' => 'Create'.$model.'Data',
                'controller' => 'Create'.$model.'Controller',
                'request_stub' => 'generic',
            ],
            [
                'stub' => 'Update',
                'name' => 'Update'.$model,
                'operation' => 'update'.$model,
                'verb' => 'PATCH',
                'url' => $url.'/{'.$entity.'}',
                'action' => 'Update'.$model.'Action',
                'request' => 'Update'.$model.'Request',
                'task' => 'Update'.$model.'Task',
                'dto' => 'Update'.$model.'Data',
                'controller' => 'Update'.$model.'Controller',
                'request_stub' => 'generic',
            ],
            [
                'stub' => 'Delete',
                'name' => 'Delete'.$model,
                'operation' => 'delete'.$model,
                'verb' => 'DELETE',
                'url' => $url.'/{'.$entity.'}',
                'action' => 'Delete'.$model.'Action',
                'request' => 'Delete'.$model.'Request',
                'task' => 'Delete'.$model.'Task',
                'dto' => 'Delete'.$model.'Data',
                'controller' => 'Delete'.$model.'Controller',
                'request_stub' => 'generic',
            ],
        ];

        foreach ($routes as $route) {
            $this->call('apiato:generate:request', [
                '--section' => $sectionName,
                '--container' => $containerName,
                '--file' => $route['request'],
                '--ui' => $ui,
                '--stub' => $route['request_stub'],
                '--model' => $model,
            ]);

            $this->call('apiato:generate:action', [
                '--section' => $sectionName,
                '--container' => $containerName,
                '--file' => $route['action'],
                '--ui' => $ui,
                '--model' => $model,
                '--stub' => $route['stub'],
            ]);

            $this->call('apiato:generate:task', [
                '--section' => $sectionName,
                '--container' => $containerName,
                '--file' => $route['task'],
                '--model' => $model,
                '--stub' => $route['stub'],
            ]);

            $this->call('apiato:generate:route', [
                '--section' => $sectionName,
                '--container' => $containerName,
                '--file' => $route['name'].'Route',
                '--ui' => $ui,
                '--operation' => $route['operation'],
                '--docversion' => $version,
                '--url' => $route['url'],
                '--verb' => $route['verb'],
                '--controller' => $route['controller'],
            ]);

            $this->call('apiato:generate:controller', [
                '--section' => $sectionName,
                '--container' => $containerName,
                '--file' => $route['controller'],
                '--ui' => $ui,
                '--stub' => $route['stub'],
            ]);

            $this->call('apiato:generate:dto', [
                '--section' => $sectionName,
                '--container' => $containerName,
                '--file' => $route['dto'],
            ]);
        }
        $this->call('apiato:generate:task', [
            '--section' => $sectionName,
            '--container' => $containerName,
            '--file' => 'Find'.$model.'ByFieldTask',
            '--model' => $model,
            '--stub' => 'field',
        ]);

        return [
            'path-parameters' => [
                'section-name' => $sectionName,
                'container-name' => $containerName,
            ],
            'stub-parameters' => [
                '_section-name' => Str::lower($sectionName),
                'section-name' => $sectionName,
                '_container-name' => Str::lower($sectionName),
                'container-name' => $containerName,
                'class-name' => $this->fileName,
            ],
            'file-parameters' => [
                'file-name' => $this->fileName,
            ],
        ];
    }

    /**
     * Get the default file name for this component to be generated.
     */
    public function getDefaultFileName(): string
    {
        return 'README';
    }

    public function getDefaultFileExtension(): string
    {
        return 'md';
    }
}
