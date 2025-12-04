<?php

namespace EasyPack\Console\Commands\Scaffolding;

use Illuminate\Support\Str;

class MakeAPIControllerCommand extends BaseScaffoldCommand
{
    /**
     * The console command name.
     *
     * @var string
     */
    protected $signature = 'make:oxygen:api-controller {name}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Scaffold an Oxygen API controller';

    /**
     * The type of class being generated.
     *
     * @var string
     */
    protected $type = 'Controller';

    /**
     * Get the stub file for the generator.
     *
     * @return string
     */
    protected function getStub()
    {
        $customStub = resource_path('stubs/oxygen/api-controller.stub');

        if (file_exists($customStub)) {
            return $customStub;
        }

        return __DIR__ . '/stubs/api-controller.stub';
    }

    /**
     * Get the default namespace for the class.
     *
     * @param  string  $rootNamespace
     * @return string
     */
    protected function getDefaultNamespace($rootNamespace)
    {
        // Use Api/V1 namespace for enterprise-grade versioning
        // This allows easy addition of V2, V3, etc. in the future
        return $rootNamespace . "\\Http\\Controllers\\Api\\V1";
    }

    protected function transformClassName($name)
    {
        return Str::studly(Str::plural($name));
    }

    protected function addClassNameSuffix($name)
    {
        // Use 'Controller' suffix (industry standard, matches Laravel convention)
        // No 'API' prefix - the folder structure already indicates it's an API controller
        return $name . 'Controller';
    }
}
