<?php

namespace EasyPack\Console\Commands\Scaffolding;

use Illuminate\Console\GeneratorCommand;
use Illuminate\Support\Str;

abstract class BaseScaffoldCommand extends GeneratorCommand
{
    /**
     * Parse the class name and format according to the root namespace.
     *
     * @param  string  $name
     * @return string
     */
    protected function qualifyClass($name)
    {
        $name = $this->transformClassName($name);

        $name = ltrim($name, '\\/');

        $name = str_replace('/', '\\', $name);

        $rootNamespace = $this->rootNamespace();

        if (Str::startsWith($name, $rootNamespace)) {
            return $this->addClassNameSuffix($name);
        }

        return $this->qualifyClass(
            $this->getDefaultNamespace(trim($rootNamespace, '\\')) . '\\' . $name
        );
    }

    protected function transformClassName($name)
    {
        return $name;
    }

    protected function addClassNameSuffix($name)
    {
        return $name;
    }

    /**
     * Build the class with the given name.
     *
     * @param  string  $name
     * @return string
     *
     * @throws \Illuminate\Contracts\Filesystem\FileNotFoundException
     */
    protected function buildClass($name)
    {
        $stub = $this->files->get($this->getStub());

        $stub = $this->compileVariables($stub);

        return $this->replaceNamespace($stub, $name)->replaceClass($stub, $name);
    }

    protected function compileVariables($stub)
    {
        $stub = str_replace('{{entityGroup}}', $this->getEntityPlural(), $stub);
        $stub = str_replace('{{entitySingular}}', $this->getEntitySingularStudly(), $stub);
        $stub = str_replace('{{entityGroupLower}}', $this->getEntityPluralLower(), $stub);
        $stub = str_replace('{{entityLower}}', $this->getEntitySingularNameLower(), $stub);
        $stub = str_replace('{{tableName}}', $this->getEntityPluralLower('snake_case'), $stub);
        $stub = str_replace('{{resourceName}}', $this->getEntityPluralLower('kebab-case'), $stub);

        return $stub;
    }

    protected function getEntityName(): string
    {
        return Str::studly($this->argument('name'));
    }

    protected function getEntitySingularName(): string
    {
        return Str::studly(Str::singular($this->getNameInput()));
    }

    protected function getEntityPlural(): string
    {
        return Str::studly(Str::plural($this->getNameInput()));
    }

    protected function getEntitySingular(): string
    {
        return Str::singular($this->getNameInput());
    }

    protected function getEntitySingularStudly(): string
    {
        return Str::studly(Str::singular($this->getNameInput()));
    }

    protected function getEntitySingularNameLower(): string
    {
        return strtolower(Str::studly(Str::singular($this->getNameInput())));
    }

    protected function getEntityPluralLower($case = 'Str::studly'): string
    {
        $entityName = Str::plural($this->getNameInput());

        switch ($case) {
            case 'snake_case':
                return Str::snake($entityName);
            case 'kebab-case':
                return Str::kebab($entityName);
        }

        return strtolower(Str::studly($entityName));
    }
}
