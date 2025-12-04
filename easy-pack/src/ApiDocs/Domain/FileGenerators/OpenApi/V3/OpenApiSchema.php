<?php

namespace EasyPack\ApiDocs\Domain\FileGenerators\OpenApi\V3;

use EasyPack\ApiDocs\Domain\FileGenerators\BaseFileGenerator;

/**
 * OpenAPI 3.0 Schema Generator
 *
 * Generates OpenAPI 3.0.x specification files from API documentation.
 * @see https://swagger.io/specification/
 */
class OpenApiSchema extends BaseFileGenerator
{
    /**
     * @var string OpenAPI version
     */
    protected string $openApiVersion = '3.0.3';

    /**
     * @var string Base path for API endpoints
     */
    protected string $basePath = '/api/v1';

    /**
     * @var array Server configurations
     */
    protected array $servers = [];

    /**
     * @var array Security schemes
     */
    protected array $securitySchemes = [];

    /**
     * @var array Default security requirements
     */
    protected array $security = [];

    /**
     * Get generated output array
     *
     * @return array
     */
    public function getOutput(): array
    {
        return $this->schema;
    }

    /**
     * Constructor
     */
    public function __construct()
    {
        $appName = config('app.name', 'API');
        $appUrl = config('app.url', 'http://localhost');
        
        // Use API docs config if available
        $title = config('api-docs.info.title') ?: "{$appName} API";
        $description = config('api-docs.info.description') ?: "API Documentation for {$appName}";
        $version = config('api-docs.info.version', '1.0.0');
        $contactName = config('api-docs.info.contact.name', 'API Support');
        $contactEmail = config('api-docs.info.contact.email');

        $contact = ['name' => $contactName];
        if ($contactEmail) {
            $contact['email'] = $contactEmail;
        }

        $this->schema = [
            'openapi' => $this->openApiVersion,
            'info' => [
                'title' => $title,
                'description' => $description,
                'version' => $version,
                'contact' => $contact,
            ],
            'servers' => [
                [
                    'url' => $appUrl,
                    'description' => 'Current Server',
                ],
            ],
            'paths' => [],
            'components' => [
                'securitySchemes' => [
                    'apiKey' => [
                        'type' => 'apiKey',
                        'in' => 'header',
                        'name' => config('api-docs.security.api_key_header', 'x-api-key'),
                        'description' => 'API Key for authentication',
                    ],
                    'bearerAuth' => [
                        'type' => 'http',
                        'scheme' => 'bearer',
                        'bearerFormat' => 'token',
                        'description' => 'Access token (x-access-token)',
                    ],
                ],
                'schemas' => [],
                'responses' => [
                    'UnauthorizedError' => [
                        'description' => 'Access token is missing or invalid',
                        'content' => [
                            'application/json' => [
                                'schema' => [
                                    '$ref' => '#/components/schemas/ApiError',
                                ],
                            ],
                        ],
                    ],
                    'ForbiddenError' => [
                        'description' => 'Access denied',
                        'content' => [
                            'application/json' => [
                                'schema' => [
                                    '$ref' => '#/components/schemas/ApiError',
                                ],
                            ],
                        ],
                    ],
                    'ValidationError' => [
                        'description' => 'Validation error',
                        'content' => [
                            'application/json' => [
                                'schema' => [
                                    '$ref' => '#/components/schemas/ValidationError',
                                ],
                            ],
                        ],
                    ],
                ],
            ],
            'security' => [
                ['apiKey' => []],
                ['bearerAuth' => []],
            ],
            'tags' => [],
        ];

        // Add default error schemas
        $this->addDefaultSchemas();
    }

    /**
     * Add default error schemas
     */
    protected function addDefaultSchemas(): void
    {
        $this->schema['components']['schemas']['ApiError'] = [
            'type' => 'object',
            'properties' => [
                'success' => [
                    'type' => 'boolean',
                    'example' => false,
                ],
                'message' => [
                    'type' => 'string',
                    'example' => 'An error occurred',
                ],
            ],
            'required' => ['success', 'message'],
        ];

        $this->schema['components']['schemas']['ValidationError'] = [
            'type' => 'object',
            'properties' => [
                'success' => [
                    'type' => 'boolean',
                    'example' => false,
                ],
                'message' => [
                    'type' => 'string',
                    'example' => 'The given data was invalid.',
                ],
                'errors' => [
                    'type' => 'object',
                    'additionalProperties' => [
                        'type' => 'array',
                        'items' => [
                            'type' => 'string',
                        ],
                    ],
                ],
            ],
            'required' => ['success', 'message', 'errors'],
        ];

        $this->schema['components']['schemas']['SuccessResponse'] = [
            'type' => 'object',
            'properties' => [
                'success' => [
                    'type' => 'boolean',
                    'example' => true,
                ],
                'message' => [
                    'type' => 'string',
                    'example' => 'Operation completed successfully',
                ],
            ],
            'required' => ['success'],
        ];

        $this->schema['components']['schemas']['PaginationMeta'] = [
            'type' => 'object',
            'properties' => [
                'current_page' => ['type' => 'integer', 'example' => 1],
                'from' => ['type' => 'integer', 'example' => 1],
                'last_page' => ['type' => 'integer', 'example' => 10],
                'per_page' => ['type' => 'integer', 'example' => 15],
                'to' => ['type' => 'integer', 'example' => 15],
                'total' => ['type' => 'integer', 'example' => 150],
            ],
        ];
    }

    /**
     * Set the server URL
     *
     * @param string $url
     * @param string $description
     * @return $this
     */
    public function setServerUrl(string $url, string $description = 'API Server'): self
    {
        $this->schema['servers'] = [
            [
                'url' => rtrim($url, '/'),
                'description' => $description,
            ],
        ];

        return $this;
    }

    /**
     * Add a server
     *
     * @param string $url
     * @param string $description
     * @return $this
     */
    public function addServer(string $url, string $description = ''): self
    {
        $this->schema['servers'][] = [
            'url' => rtrim($url, '/'),
            'description' => $description,
        ];

        return $this;
    }

    /**
     * Set the base path (prepended to all paths)
     *
     * @param string $basePath
     * @return $this
     */
    public function setBasePath(string $basePath): self
    {
        // In OpenAPI 3.0, base path is part of server URL
        // We'll store it for use when adding paths
        $this->basePath = $basePath;

        return $this;
    }

    /**
     * Add path data for an endpoint
     *
     * @param string $path
     * @param string $method
     * @param array $data
     * @return $this
     */
    public function addPathData(string $path, string $method, array $data): self
    {
        // Convert from Swagger 2.0 format to OpenAPI 3.0
        $openApiPath = $this->convertPathData($data, $method);

        if (!isset($this->schema['paths'][$path])) {
            $this->schema['paths'][$path] = [];
        }

        $this->schema['paths'][$path][strtolower($method)] = $openApiPath;

        // Add tag if not exists
        $tags = $data['tags'] ?? [];
        foreach ($tags as $tag) {
            $this->addTag($tag);
        }

        return $this;
    }

    /**
     * Convert Swagger 2.0 path data to OpenAPI 3.0
     *
     * @param array $data
     * @param string $method
     * @return array
     */
    protected function convertPathData(array $data, string $method): array
    {
        $method = strtolower($method);
        $openApiData = [
            'tags' => $data['tags'] ?? [],
            'summary' => $data['summary'] ?? '',
            'description' => $data['description'] ?? '',
            'operationId' => $data['operationId'] ?? '',
            'responses' => $this->convertResponses($data['responses'] ?? []),
        ];

        // Convert parameters
        $parameters = [];
        $requestBody = null;

        foreach ($data['parameters'] ?? [] as $param) {
            $converted = $this->convertParameter($param, $method);

            if (isset($converted['requestBody'])) {
                $requestBody = $converted['requestBody'];
            } else {
                $parameters[] = $converted;
            }
        }

        if (!empty($parameters)) {
            $openApiData['parameters'] = $parameters;
        }

        if ($requestBody && in_array($method, ['post', 'put', 'patch'])) {
            $openApiData['requestBody'] = $requestBody;
        }

        // Convert security
        if (!empty($data['security'])) {
            $openApiData['security'] = $this->convertSecurity($data['security']);
        }

        return $openApiData;
    }

    /**
     * Convert a Swagger 2.0 parameter to OpenAPI 3.0
     *
     * @param array $param
     * @param string $method
     * @return array
     */
    protected function convertParameter(array $param, string $method): array
    {
        $location = $param['in'] ?? 'query';

        // In OpenAPI 3.0, body and formData parameters become requestBody
        if (in_array($location, ['body', 'formData'])) {
            return [
                'requestBody' => $this->createRequestBody($param, $location),
            ];
        }

        $openApiParam = [
            'name' => $param['name'] ?? '',
            'in' => $location,
            'required' => $param['required'] ?? false,
            'description' => $param['description'] ?? '',
        ];

        // Add schema
        $openApiParam['schema'] = $this->convertSchema($param);

        return $openApiParam;
    }

    /**
     * Create request body from parameter
     *
     * @param array $param
     * @param string $location
     * @return array
     */
    protected function createRequestBody(array $param, string $location): array
    {
        $contentType = $location === 'formData'
            ? 'application/x-www-form-urlencoded'
            : 'application/json';

        // Check if it's a $ref
        if (isset($param['schema']['$ref'])) {
            $ref = str_replace('#/definitions/', '#/components/schemas/', $param['schema']['$ref']);
            return [
                'required' => $param['required'] ?? false,
                'content' => [
                    $contentType => [
                        'schema' => ['$ref' => $ref],
                    ],
                ],
            ];
        }

        return [
            'required' => $param['required'] ?? false,
            'content' => [
                $contentType => [
                    'schema' => $this->convertSchema($param),
                ],
            ],
        ];
    }

    /**
     * Convert Swagger 2.0 schema to OpenAPI 3.0
     *
     * @param array $param
     * @return array
     */
    protected function convertSchema(array $param): array
    {
        $schema = [];

        if (isset($param['schema'])) {
            // Convert $ref from definitions to components/schemas
            if (isset($param['schema']['$ref'])) {
                return [
                    '$ref' => str_replace('#/definitions/', '#/components/schemas/', $param['schema']['$ref']),
                ];
            }
            return $param['schema'];
        }

        $type = strtolower($param['type'] ?? 'string');

        // Map Swagger 2.0 types to OpenAPI 3.0
        $schema['type'] = $type === 'file' ? 'string' : $type;

        if ($type === 'file') {
            $schema['format'] = 'binary';
        }

        if (isset($param['format'])) {
            $schema['format'] = $param['format'];
        }

        if (isset($param['example'])) {
            $schema['example'] = $param['example'];
        }

        if ($type === 'array' && isset($param['items'])) {
            $schema['items'] = $param['items'];
        }

        return $schema;
    }

    /**
     * Convert Swagger 2.0 responses to OpenAPI 3.0
     *
     * @param array $responses
     * @return array
     */
    protected function convertResponses(array $responses): array
    {
        $openApiResponses = [];

        foreach ($responses as $code => $response) {
            $openApiResponse = [
                'description' => $response['description'] ?? '',
            ];

            // Convert schema to content
            if (isset($response['schema'])) {
                $schema = $response['schema'];

                // Convert $ref from definitions to components/schemas
                if (isset($schema['$ref'])) {
                    $schema['$ref'] = str_replace('#/definitions/', '#/components/schemas/', $schema['$ref']);
                }

                $openApiResponse['content'] = [
                    'application/json' => [
                        'schema' => $schema,
                    ],
                ];
            }

            $openApiResponses[(string)$code] = $openApiResponse;
        }

        return $openApiResponses;
    }

    /**
     * Convert Swagger 2.0 security to OpenAPI 3.0
     *
     * @param array $security
     * @return array
     */
    protected function convertSecurity(array $security): array
    {
        $openApiSecurity = [];

        foreach ($security as $securityItem) {
            $converted = [];
            foreach ($securityItem as $name => $scopes) {
                // Map to our security scheme names
                $mappedName = match ($name) {
                    'apiKey' => 'apiKey',
                    'accessToken' => 'bearerAuth',
                    default => $name,
                };
                $converted[$mappedName] = $scopes;
            }
            $openApiSecurity[] = $converted;
        }

        return $openApiSecurity;
    }

    /**
     * Add a tag
     *
     * @param string $name
     * @param string $description
     * @return $this
     */
    public function addTag(string $name, string $description = ''): self
    {
        // Check if tag already exists
        foreach ($this->schema['tags'] as $tag) {
            if ($tag['name'] === $name) {
                return $this;
            }
        }

        $this->schema['tags'][] = [
            'name' => $name,
            'description' => $description,
        ];

        return $this;
    }

    /**
     * Add a schema definition to components
     *
     * @param string $name
     * @param array $schema
     * @return $this
     */
    public function addSchema(string $name, array $schema): self
    {
        $this->schema['components']['schemas'][$name] = $schema;

        return $this;
    }

    /**
     * Add definitions (bulk add schemas)
     *
     * @param string $key
     * @param mixed $value
     * @return $this
     */
    public function addToSchema($key, $value)
    {
        if ($key === 'definitions' && is_array($value)) {
            // Convert Swagger 2.0 definitions to OpenAPI 3.0 components/schemas
            foreach ($value as $name => $definition) {
                $this->schema['components']['schemas'][$name] = $this->convertDefinition($definition);
            }
        }

        return $this;
    }

    /**
     * Convert a Swagger 2.0 definition to OpenAPI 3.0 schema
     *
     * @param array $definition
     * @return array
     */
    protected function convertDefinition(array $definition): array
    {
        // Update any $ref to use components/schemas
        return $this->updateRefs($definition);
    }

    /**
     * Recursively update $ref paths in the schema
     *
     * @param mixed $data
     * @return mixed
     */
    protected function updateRefs($data)
    {
        if (!is_array($data)) {
            return $data;
        }

        $result = [];
        foreach ($data as $key => $value) {
            if ($key === '$ref' && is_string($value)) {
                $result[$key] = str_replace('#/definitions/', '#/components/schemas/', $value);
            } else {
                $result[$key] = $this->updateRefs($value);
            }
        }

        return $result;
    }

    /**
     * Set API info
     *
     * @param array $info
     * @return $this
     */
    public function setInfo(array $info): self
    {
        $this->schema['info'] = array_merge($this->schema['info'], $info);

        return $this;
    }

    /**
     * Get the OpenAPI version
     *
     * @return string
     */
    public function getOpenApiVersion(): string
    {
        return $this->openApiVersion;
    }
}
