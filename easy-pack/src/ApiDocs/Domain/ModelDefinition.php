<?php

namespace EasyPack\ApiDocs\Domain;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\File;
use EasyPack\ApiDocs\Docs\Param;

class ModelDefinition
{
    /**
     * Easy Pack package models to include in definitions.
     * These are always scanned regardless of config directories.
     *
     * @var array<string>
     */
    protected static array $packageModels = [
        \EasyPack\Models\User::class,
        \EasyPack\Models\Setting::class,
        \EasyPack\Models\SettingGroup::class,
        \EasyPack\Models\NotificationPreference::class,
        \EasyPack\Models\PushNotification::class,
        \EasyPack\Models\PersonalAccessToken::class,
    ];

    /**
     * Custom model definitions that can be registered programmatically.
     * Useful for third-party packages like Spatie Media Library.
     *
     * @var array<string, array>
     */
    protected static array $customDefinitions = [];

    /**
     * Register a custom model definition.
     * This allows packages to add their model schemas without modifying core code.
     *
     * @param string $name The definition name (e.g., 'Media')
     * @param array $definition The Swagger/OpenAPI schema definition
     */
    public static function registerCustomDefinition(string $name, array $definition): void
    {
        static::$customDefinitions[$name] = $definition;
    }

    /**
     * Get all model definitions
     *
     * @return array
     */
    public function getAllModelDefinitions()
    {
        // Check if model definitions should be included
        if (!config('api-docs.include_model_definitions', true)) {
            return [];
        }

        $models = $this->getAllModels();
        $hiddenModelDefinitions = config('api-docs.hidden_model_definition_classes', []);

        $definitions = [];

        // First, add custom/third-party definitions (like Spatie Media)
        foreach (static::$customDefinitions as $name => $definition) {
            if (!in_array($name, $hiddenModelDefinitions)) {
                $definitions[$name] = $definition;
            }
        }

        // Add Media definition for Spatie Media Library
        if (!isset($definitions['Media']) && !in_array('Media', $hiddenModelDefinitions)) {
            $definitions['Media'] = $this->getSpatieMediaDefinition();
        }

        // Then add model definitions
        foreach ($models as $model) {
            $definition = $this->getModelDefinition($model);

            // if hidden, don't include them
            if (!in_array($definition['name'], $hiddenModelDefinitions)) {
                // if already included, don't include them
                // this will prioritise the models in local project first
                if (!isset($definitions[$definition['name']])) {
                    $definitions[$definition['name']] = $definition['definition'];
                }
            }
        }

        // Add standard response definitions
        $definitions['SuccessResponse'] = [
            'type' => 'object',
            'properties' => [
                'message' => ['type' => 'string'],
                'result' => ['type' => 'boolean', 'default' => true],
                'payload' => ['type' => 'object'],
            ],
        ];

        $definitions['Paginator'] = [
            'type' => 'object',
            'properties' => [
                'current_page' => ['type' => 'number'],
                'per_page' => ['type' => 'number', 'default' => 50],
                'from' => ['type' => 'number'],
                'to' => ['type' => 'number'],
                'total' => ['type' => 'number'],
                'last_page' => ['type' => 'number'],
            ],
        ];

        return $definitions;
    }

    /**
     * Get Spatie Media Library model definition.
     * Since Media comes from a third-party package, we define its schema explicitly.
     *
     * @return array
     */
    protected function getSpatieMediaDefinition(): array
    {
        return [
            'type' => 'object',
            'properties' => [
                'id' => ['type' => 'integer'],
                'uuid' => ['type' => 'string'],
                'name' => ['type' => 'string'],
                'file_name' => ['type' => 'string'],
                'mime_type' => ['type' => 'string'],
                'size' => ['type' => 'integer'],
                'collection_name' => ['type' => 'string'],
                'disk' => ['type' => 'string'],
                'custom_properties' => ['type' => 'object'],
                'responsive_images' => ['type' => 'object'],
                'order_column' => ['type' => 'integer'],
                'original_url' => ['type' => 'string'],
                'preview_url' => ['type' => 'string'],
                'created_at' => ['type' => 'string'],
                'updated_at' => ['type' => 'string'],
            ],
        ];
    }

    /**
     * Return the default error definitions
     *
     * @return array
     */
    public function getAllErrorDefinitions()
    {
        return [
            'ApiErrorUnauthorized' => [
                'type' => 'object',
                'properties' => [
                    'message' => ['type' => 'string'],
                    'result' => ['type' => 'boolean', 'default' => false],
                    'payload' => ['type' => 'object'],
                ],
            ],
            'ApiErrorAccessDenied' => [
                'type' => 'object',
                'properties' => [
                    'message' => ['type' => 'string'],
                    'result' => ['type' => 'boolean', 'default' => false],
                    'payload' => ['type' => 'object'],
                ],
            ],
            'ApiError' => [
                'type' => 'object',
                'properties' => [
                    'message' => ['type' => 'string'],
                    'result' => ['type' => 'boolean', 'default' => false],
                    'payload' => ['type' => 'object'],
                ],
            ],
        ];
    }

    /**
     * Get success response definition
     *
     * @param string $responseName
     * @param string $successObject
     * @return array
     */
    public function getSuccessResponseDefinition($responseName, $successObject)
    {
        $shortName = self::getModelShortName($successObject);

        return [
            $responseName => [
                'type' => 'object',
                'properties' => [
                    'message' => ['type' => 'string'],
                    'result' => ['type' => 'boolean', 'default' => true],
                    'payload' => ['$ref' => '#/definitions/' . $shortName],
                ],
            ]
        ];
    }

    /**
     * Get success paginated response definition
     *
     * @param string $responseName
     * @param string $successObject
     * @return array
     */
    public function getSuccessResponsePaginatedDefinition($responseName, $successObject)
    {
        $shortName = self::getModelShortName($successObject);

        return [
            $responseName => [
                'type' => 'object',
                'properties' => [
                    'message' => ['type' => 'string'],
                    'result' => ['type' => 'boolean', 'default' => true],
                    'payload' => [
                        'type' => 'array',
                        'items' => [
                            '$ref' => '#/definitions/' . $shortName
                        ],
                    ],
                    'paginator' => ['$ref' => '#/definitions/Paginator'],
                ],
            ]
        ];
    }

    /**
     * Return all declared definitions
     *
     * @return array
     */
    public function getAllDefinitions()
    {
        return array_merge($this->getAllModelDefinitions(), $this->getAllErrorDefinitions());
    }

    /**
     * Get all models for this project
     *
     * @return array
     */
    protected function getAllModels()
    {
        $models = [];

        // First, add package models (these are known and don't need file scanning)
        foreach (static::$packageModels as $modelClass) {
            if (class_exists($modelClass)) {
                $models[] = $modelClass;
            }
        }

        // Scan app/Models directory
        $directories = [
            app_path('Models'),
        ];

        $appendModelDirectories = config('api-docs.model_directories', []);
        if (!empty($appendModelDirectories)) {
            $directories = array_merge($directories, $appendModelDirectories);
        }

        foreach ($directories as $dirPath) {
            if (File::isDirectory($dirPath)) {
                $this->includeAllFilesFromDirRecursive($dirPath);
            }
        }

        foreach (get_declared_classes() as $class) {
            if (is_subclass_of($class, Model::class)) {
                // Avoid duplicates
                if (!in_array($class, $models)) {
                    $models[] = $class;
                }
            }
        }

        return $models;
    }

    /**
     * Include all files from directory recursively
     *
     * @param string $dirPath
     */
    protected function includeAllFilesFromDirRecursive($dirPath)
    {
        $files = File::allFiles($dirPath);

        foreach ($files as $file) {
            if ($file->getExtension() === 'php') {
                require_once $file->getRealPath();
            }
        }
    }

    /**
     * Build the model definition for a given model
     *
     * @param string $class
     * @return array
     */
    protected function getModelDefinition($class): array
    {
        $model = new $class;
        $fields = [];

        // Get visible fields
        $visibleFields = $model->getVisible();
        $hiddenFields = $model->getHidden();

        // If visible is empty, assume all fields except hidden
        if (empty($visibleFields)) {
            // Try to get fillable fields as a fallback
            $fillableFields = $model->getFillable();
            if (!empty($fillableFields)) {
                $visibleFields = $fillableFields;
            }
        }

        // Get casts for proper type mapping
        $casts = method_exists($model, 'getCasts') ? $model->getCasts() : [];

        // Build field list
        $filteredFields = [];
        if (!empty($visibleFields)) {
            foreach ($visibleFields as $field) {
                if (!in_array($field, $hiddenFields)) {
                    // Determine type from casts or default to string
                    $type = $this->mapCastToSwaggerType($casts[$field] ?? null, $field);
                    $filteredFields[$field] = $type;
                }
            }
        }

        // If model has getExtraApiFields method, add those
        if (method_exists($model, 'getExtraApiFields')) {
            $extraFields = $model->getExtraApiFields();
            foreach ($extraFields as $key => $value) {
                if (is_int($key)) {
                    $filteredFields[$value] = 'string';
                } else {
                    $filteredFields[$key] = $value;
                }
            }
        }

        // Build properties
        $properties = [];
        foreach ($filteredFields as $key => $value) {
            if (is_array($value) && isset($value['type'])) {
                $properties[$key] = $value;
            } else {
                $properties[$key] = ['type' => $value];
            }
        }

        $reflect = new \ReflectionClass($class);

        $response = [
            'name' => $reflect->getShortName(),
            'definition' => [
                'type' => 'object',
            ],
        ];

        // empty properties are not allowed
        if (count($properties) > 0) {
            $response['definition']['properties'] = $properties;
        }

        return $response;
    }

    /**
     * Map Laravel cast types to Swagger/OpenAPI types
     *
     * @param string|null $cast
     * @param string $fieldName
     * @return string
     */
    protected function mapCastToSwaggerType(?string $cast, string $fieldName): string
    {
        // Check field name patterns first
        if (str_ends_with($fieldName, '_id') || $fieldName === 'id') {
            return 'integer';
        }
        if (str_ends_with($fieldName, '_at')) {
            return 'string'; // datetime
        }
        if (str_starts_with($fieldName, 'is_') || str_starts_with($fieldName, 'has_')) {
            return 'boolean';
        }

        if ($cast === null) {
            return 'string';
        }

        // Map common Laravel casts to Swagger types
        return match ($cast) {
            'int', 'integer' => 'integer',
            'real', 'float', 'double' => 'number',
            'string' => 'string',
            'bool', 'boolean' => 'boolean',
            'object', 'array', 'json', 'collection' => 'object',
            'date', 'datetime', 'immutable_date', 'immutable_datetime', 'timestamp' => 'string',
            'hashed' => 'string',
            default => 'string',
        };
    }

    /**
     * Get model short name
     *
     * @param string $class
     * @return string
     */
    public static function getModelShortName($class)
    {
        $reflect = new \ReflectionClass($class);
        return $reflect->getShortName();
    }
}
