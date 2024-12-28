<?php

namespace App\Console\Commands;

use Illuminate\Console\GeneratorCommand;

class MakeApiFilter extends GeneratorCommand
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'make:api-filter {name}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Create a new API filter class (version 1)';

    protected $type = 'ApiFilter';

    protected function getStub()
    {
        return base_path('stubs/api-filter.stub');
    }

    protected function getDefaultNamespace($rootNamespace)
    {
        return $rootNamespace . '\Http\Filters';
    }
}
