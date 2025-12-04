<?php
namespace EasyPack\ApiDocs\Domain\FileGenerators;

use Illuminate\Support\Facades\File;
use EasyPack\ApiDocs\Exceptions\FileGenerationFailedException;
use Symfony\Component\Yaml\Yaml;

abstract class BaseFileGenerator
{
    protected $schema = [];

    abstract public function getOutput();

    /**
     * Add data to schema
     *
     * @param string $key
     * @param mixed $value
     * @return $this
     */
    public function addToSchema($key, $value)
    {
        $this->schema[$key] = $value;
        return $this;
    }

    /**
     * Append to a schema array
     *
     * @param string $key
     * @param mixed $value
     * @return $this
     */
    public function appendToSchemaArray($key, $value)
    {
        $this->schema[$key][] = $value;
        return $this;
    }

    /**
     * Write generated output to a JSON file
     *
     * @param string $outputFilePath
     * @param bool $overwrite
     * @return bool
     * @throws FileGenerationFailedException
     */
    public function writeOutputFileJson($outputFilePath, $overwrite = true): bool
    {
        if (!$overwrite && file_exists($outputFilePath)) {
            throw new FileGenerationFailedException("File {$outputFilePath} already exists.");
        }

        try {
            $outputString = json_encode($this->getOutput(), JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
        } catch (\Exception $ex) {
            throw new FileGenerationFailedException("Failed to generate a valid output. " . $ex->getMessage());
        }

        $outputDir = dirname($outputFilePath);
        if (!File::isDirectory($outputDir)) {
            File::makeDirectory($outputDir, 0755, true);
        }

        file_put_contents($outputFilePath, $outputString);

        return true;
    }

    /**
     * Write generated output to a YAML file
     *
     * @param string $outputFilePath
     * @param bool $overwrite
     * @return bool
     * @throws FileGenerationFailedException
     */
    public function writeOutputFileYaml($outputFilePath, $overwrite = true): bool
    {
        if (!$overwrite && file_exists($outputFilePath)) {
            throw new FileGenerationFailedException("File {$outputFilePath} already exists.");
        }

        $outputDir = dirname($outputFilePath);
        if (!File::isDirectory($outputDir)) {
            File::makeDirectory($outputDir, 0755, true);
        }

        try {
            $yamlString = Yaml::dump($this->getOutput(), 10, 4, Yaml::DUMP_EMPTY_ARRAY_AS_SEQUENCE|Yaml::DUMP_OBJECT_AS_MAP);
            file_put_contents($outputFilePath, $yamlString);
        } catch (\Exception $ex) {
            throw new FileGenerationFailedException("Failed to generate a valid output. " . $ex->getMessage());
        }

        return true;
    }

    /**
     * Get schema
     *
     * @return array
     */
    public function getSchema()
    {
        return $this->schema;
    }
}
