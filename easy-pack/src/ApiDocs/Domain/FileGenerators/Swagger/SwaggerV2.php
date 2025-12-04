<?php

namespace EasyPack\ApiDocs\Domain\FileGenerators\Swagger;

use EasyPack\ApiDocs\Domain\FileGenerators\BaseFileGenerator;

class SwaggerV2 extends BaseFileGenerator
{
    public function __construct()
    {
        $this->schema = $this->getDefaultSchema();
    }

    /**
     * Return the base template
     * https://swagger.io/docs/specification/2-0/basic-structure/
     *
     * @return array
     */
    protected function getDefaultSchema()
    {
        $title = config('api-docs.info.title') ?: config('app.name') . ' API';
        $description = config('api-docs.info.description', '');
        $version = config('api-docs.info.version', '1.0.0');

        $info = [
            'title' => $title,
            'version' => $version . '.' . date('Ymd'),
        ];

        if (!empty($description)) {
            $info['description'] = $description;
        }

        $contactName = config('api-docs.info.contact.name');
        $contactEmail = config('api-docs.info.contact.email');
        if ($contactName || $contactEmail) {
            $info['contact'] = array_filter([
                'name' => $contactName,
                'email' => $contactEmail,
            ]);
        }

        return [
            'swagger' => '2.0',
            'info' => $info,
            'host' => null,
            'schemes' => [],
            'basePath' => null,
            'paths' => [],
            'securityDefinitions' => [
                'apiKey' => [
                    'type' => 'apiKey',
                    'name' => config('api-docs.security.api_key_header', 'x-api-key'),
                    'in' => 'header',
                    'description' => 'API Key for application',
                ],
                'accessToken' => [
                    'type' => 'apiKey',
                    'name' => config('api-docs.security.access_token_header', 'x-access-token'),
                    'in' => 'header',
                    'description' => 'Unique user authentication token',
                ],
            ],
        ];
    }

    /**
     * Set basePath of API
     *
     * @param string $basePath
     * @return $this
     */
    public function setBasePath($basePath)
    {
        $this->addToSchema('basePath', $basePath);
        return $this;
    }

    /**
     * Set host
     *
     * @param string $host
     * @return $this
     */
    public function setHost($host)
    {
        $this->addToSchema('host', $host);
        return $this;
    }

    /**
     * Set the Server host and schemes from a URL for OpenApi 2 Spec
     *
     * @example https://api.example.com
     * @example https://api.example.com/v1
     *
     * @param string $serverUrl
     * @return $this
     */
    public function setServerUrl($serverUrl)
    {
        $this->setHost(parse_url($serverUrl, PHP_URL_HOST));
        $this->setSchemes([parse_url($serverUrl, PHP_URL_SCHEME)]);
        return $this;
    }

    /**
     * Set schemes (protocols)
     *
     * @param array $schemes
     * @return $this
     */
    public function setSchemes($schemes)
    {
        $this->addToSchema('schemes', $schemes);
        return $this;
    }

    /**
     * Add paths to Schema
     *
     * @param string $pathSuffix
     * @param string $method
     * @param array $data
     * @return $this
     */
    public function addPathData($pathSuffix, $method, $data)
    {
        $this->schema['paths'][$pathSuffix][$method] = $data;
        return $this;
    }

    /**
     * Add or merge definitions to Schema
     *
     * @param array $definitions
     * @return $this
     */
    public function addDefinitions(array $definitions)
    {
        if (!isset($this->schema['definitions'])) {
            $this->schema['definitions'] = [];
        }

        $this->schema['definitions'] = array_merge($this->schema['definitions'], $definitions);
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
