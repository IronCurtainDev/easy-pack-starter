<?php

namespace EasyPack\Console\Commands\Scaffolding;

use Illuminate\Support\Str;

class MakeEasyPackModelCommand extends BaseScaffoldCommand
{
    /**
     * The console command name.
     *
     * @var string
     */
    protected $signature = 'make:easypack:model {name}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Scaffold an Oxygen model with repository pattern';

    /**
     * The type of class being generated.
     *
     * @var string
     */
    protected $type = 'Model';

    /**
     * Get the stub file for the generator.
     *
     * @return string
     */
    protected function getStub()
    {
        $customStub = resource_path('stubs/oxygen/model.stub');

        if (file_exists($customStub)) {
            return $customStub;
        }

        return __DIR__ . '/stubs/model.stub';
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
        return Str::studly($name);
    }
}
