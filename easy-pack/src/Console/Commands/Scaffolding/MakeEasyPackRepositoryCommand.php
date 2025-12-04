<?php

namespace EasyPack\Console\Commands\Scaffolding;

use Illuminate\Support\Str;

class MakeEasyPackRepositoryCommand extends BaseScaffoldCommand
{
    /**
     * The console command name.
     *
     * @var string
     */
    protected $signature = 'make:easypack:repository {name}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Scaffold an Oxygen repository';

    /**
     * The type of class being generated.
     *
     * @var string
     */
    protected $type = 'Repository';

    /**
     * Get the stub file for the generator.
     *
     * @return string
     */
    protected function getStub()
    {
        $customStub = resource_path('stubs/oxygen/repository.stub');

        if (file_exists($customStub)) {
            return $customStub;
        }

        return __DIR__ . '/stubs/repository.stub';
    }

    /**
     * Get the default namespace for the class.
     *
     * @param  string  $rootNamespace
     * @return string
     */
    protected function getDefaultNamespace($rootNamespace)
    {
        return $rootNamespace . "\\Entities\\" . $this->getEntityPlural();
    }

    protected function transformClassName($name)
    {
        return Str::studly(Str::plural($name));
    }

    protected function addClassNameSuffix($name)
    {
        return $name . 'Repository';
    }
}
