<?php

namespace Apiato\Core\Generator\Commands;

use Apiato\Core\Generator\GeneratorCommand;
use Apiato\Core\Generator\Interfaces\ComponentsGenerator;
use Illuminate\Support\Pluralizer;
use Illuminate\Support\Str;
use Symfony\Component\Console\Input\InputOption;

class HttpRequestGenerator extends GeneratorCommand implements ComponentsGenerator
{
    /**
     * User required/optional inputs expected to be passed while calling the command.
     * This is a replacement of the `getArguments` function "which reads whenever it's called".
     */
    public array $inputs = [
        ['requestname', null, InputOption::VALUE_OPTIONAL, 'The name of the request to be generated (CreateBook, UpdateBook, ...)'],
        ['docversion', null, InputOption::VALUE_OPTIONAL, 'The version of all endpoints to be generated (1, 2, ...)'],
        ['url', null, InputOption::VALUE_OPTIONAL, 'The base URI of all endpoints (/books, /cars, ...)'],
    ];

    /**
     * The console command name.
     *
     * @var string
     */
    protected $name = 'apiato:generate:http-request';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Create a new HTTP Request';

    /**
     * The type of class being generated.
     */
    protected string $fileType = 'HttpRequest';

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

        // container name as inputted and lower
        $containerName = $this->containerName;

        // create the default routes for this container
        $this->printInfoMessage('Generating Route');
        $version = $this->checkParameterOrAsk('docversion', 'Enter the version for all API endpoints (integer)', 1);
        $requestName = $this->checkParameterOrAsk('requestname', 'Enter the name of the request to be generated (CreateBook, UpdateBook, ...)', 'Default');
        // get the URI and remove the first trailing slash
        $url = Str::lower($this->checkParameterOrAsk('url', 'Enter the base URI for all API endpoints (foo/bar/{id})', 'default'));
        $url = ltrim($url, '/');

        $this->printInfoMessage('Generating Request');
        $this->printInfoMessage('Generating Action');
        $this->printInfoMessage('Generating Controller');

        $route = [
            'name' => $requestName,
            'operation' => $requestName,
            'verb' => 'GET',
            'url' => $url,
            'action' => $requestName.'Action',
            'request' => $requestName.'Request',
            'dto' => $requestName.'Data',
            'controller' => $requestName.'Controller',
            'request_stub' => 'generic',
        ];

        $this->call('apiato:generate:request', [
            '--section' => $sectionName,
            '--container' => $containerName,
            '--file' => $route['request'],
            '--ui' => $ui,
            '--stub' => $route['request_stub'],
        ]);

        $this->call('apiato:generate:action', [
            '--section' => $sectionName,
            '--container' => $containerName,
            '--file' => $route['action'],
            '--ui' => $ui,
            '--stub' => 'http',
            '--request' => $requestName,
            '--model' => $requestName,
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
            '--stub' => 'http',
            '--request' => $requestName,
        ]);

        $this->call('apiato:generate:dto', [
            '--section' => $sectionName,
            '--container' => $containerName,
            '--file' => $route['dto'],
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
