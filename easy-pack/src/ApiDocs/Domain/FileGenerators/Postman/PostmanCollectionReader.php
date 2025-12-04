<?php

namespace EasyPack\ApiDocs\Domain\FileGenerators\Postman;

use Symfony\Component\Yaml\Yaml;

class PostmanCollectionReader
{
    protected array $schema = [];
    protected string $pathVersion = 'v1';

    /**
     * Read and parse the Postman collection YAML file
     *
     * @param string|null $filePath
     * @return $this
     */
    public function loadFromYaml(?string $filePath = null): self
    {
        $filePath = $filePath ?? public_path('docs/postman_collection.yml');

        if (!file_exists($filePath)) {
            // Try JSON file
            $jsonPath = str_replace('.yml', '.json', $filePath);
            if (file_exists($jsonPath)) {
                $this->schema = json_decode(file_get_contents($jsonPath), true);
            } else {
                throw new \RuntimeException("Postman collection file not found: $filePath");
            }
        } else {
            $this->schema = Yaml::parseFile($filePath);
        }

        // Extract version from basePath
        if (isset($this->schema['basePath'])) {
            preg_match('/v(\d+)/', $this->schema['basePath'], $matches);
            if (!empty($matches[1])) {
                $this->pathVersion = 'v' . $matches[1];
            }
        }

        return $this;
    }

    /**
     * Read and parse the Swagger/OpenAPI JSON file
     *
     * @param string|null $filePath
     * @return $this
     */
    public function loadFromJson(?string $filePath = null): self
    {
        $filePath = $filePath ?? public_path('docs/swagger.json');

        if (!file_exists($filePath)) {
            throw new \RuntimeException("Swagger file not found: $filePath");
        }

        $this->schema = json_decode(file_get_contents($filePath), true);

        // Extract version from basePath
        if (isset($this->schema['basePath'])) {
            preg_match('/v(\d+)/', $this->schema['basePath'], $matches);
            if (!empty($matches[1])) {
                $this->pathVersion = 'v' . $matches[1];
            }
        }

        return $this;
    }

    /**
     * Get path data for test generation
     *
     * @return array
     */
    public function getPathData(): array
    {
        if (empty($this->schema)) {
            $this->loadFromJson();
        }

        $pathData = [];

        if (!isset($this->schema['paths'])) {
            return $pathData;
        }

        foreach ($this->schema['paths'] as $path => $methods) {
            foreach ($methods as $method => $definition) {
                $pathData[] = [
                    'uri' => $path,
                    'method' => strtoupper($method),
                    'summary' => $definition['summary'] ?? '',
                    'description' => $definition['description'] ?? '',
                    'tags' => $definition['tags'] ?? [],
                    'operationId' => $definition['operationId'] ?? '',
                    'parameters' => $definition['parameters'] ?? [],
                    'consumes' => $definition['consumes'] ?? [],
                    'produces' => $definition['produces'] ?? [],
                    'responses' => $definition['responses'] ?? [],
                    'security' => $definition['security'] ?? [],
                ];
            }
        }

        return $pathData;
    }

    /**
     * Get the API version from the schema
     *
     * @return string
     */
    public function getPathVersion(): string
    {
        return $this->pathVersion;
    }

    /**
     * Get the full schema
     *
     * @return array
     */
    public function getSchema(): array
    {
        return $this->schema;
    }

    /**
     * Get definitions from the schema
     *
     * @return array
     */
    public function getDefinitions(): array
    {
        return $this->schema['definitions'] ?? [];
    }

    /**
     * Get info section from the schema
     *
     * @return array
     */
    public function getInfo(): array
    {
        return $this->schema['info'] ?? [];
    }
}
