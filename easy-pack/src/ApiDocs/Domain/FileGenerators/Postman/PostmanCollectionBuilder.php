<?php

namespace EasyPack\ApiDocs\Domain\FileGenerators\Postman;

use EasyPack\ApiDocs\Domain\FileGenerators\BaseFileGenerator;

class PostmanCollectionBuilder extends BaseFileGenerator
{
    public function __construct()
    {
        $this->schema = [
            'info' => [
                'name' => config('app.name') . ' API',
                'schema' => 'https://schema.getpostman.com/json/collection/v2.1.0/collection.json',
            ],
            'item' => [],
        ];
    }

    /**
     * Set schema from Swagger schema
     *
     * @param array $swaggerSchema
     * @return $this
     */
    public function setSchema($swaggerSchema)
    {
        // Convert Swagger paths to Postman collection items
        if (isset($swaggerSchema['paths'])) {
            foreach ($swaggerSchema['paths'] as $path => $methods) {
                foreach ($methods as $method => $definition) {
                    $this->addRequest($path, $method, $definition);
                }
            }
        }

        return $this;
    }

    /**
     * Add a request to the collection
     *
     * @param string $path
     * @param string $method
     * @param array $definition
     * @return $this
     */
    protected function addRequest($path, $method, $definition)
    {
        $request = [
            'name' => $definition['summary'] ?? $path,
            'request' => [
                'method' => strtoupper($method),
                'header' => [],
                'url' => [
                    'raw' => '{{scheme}}://{{host}}' . ($definition['basePath'] ?? '') . $path,
                    'protocol' => '{{scheme}}',
                    'host' => ['{{host}}'],
                    'path' => array_filter(explode('/', $path)),
                ],
            ],
        ];

        // Add description if available
        if (!empty($definition['description'])) {
            $request['request']['description'] = $definition['description'];
        }

        $this->schema['item'][] = $request;

        return $this;
    }

    /**
     * Get generated output array
     *
     * @return array
     */
    public function getOutput()
    {
        return $this->schema;
    }
}
