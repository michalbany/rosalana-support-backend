<?php

namespace App\Console\Commands;

use Illuminate\Console\GeneratorCommand;

class MakeApiResource extends GeneratorCommand
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'make:api-resource {name}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Create a new API resource class (version 1)';

    protected $type = 'ApiResource';

    protected function getStub()
    {
        return base_path('stubs/api-resource.stub');
    }

    protected function getDefaultNamespace($rootNamespace)
    {
        return $rootNamespace . '\Http\Resources\v1';
    }
}
