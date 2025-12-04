<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Include Model Definitions
    |--------------------------------------------------------------------------
    |
    | When enabled, model definitions will be automatically generated from
    | your Eloquent models and included in the Swagger documentation.
    | This helps Android and iOS developers who use Swagger-generated
    | models for their apps.
    |
    | Models should define a $visible array to control which fields appear.
    |
    */
    'include_model_definitions' => env('API_DOCS_INCLUDE_MODELS', true),

    /*
    |--------------------------------------------------------------------------
    | Model Directories
    |--------------------------------------------------------------------------
    |
    | Additional directories to scan for Eloquent models. The app/Models
    | directory is always scanned by default. Package models from
    | easypack/starter are automatically included.
    |
    */
    'model_directories' => [
        // app_path('Entities'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Hidden Model Definition Classes
    |--------------------------------------------------------------------------
    |
    | Model class names (short names) that should be excluded from the
    | generated documentation definitions. Use the short class name
    | (e.g., 'User' not 'App\Models\User').
    |
    */
    'hidden_model_definition_classes' => [
        // 'User',
        // 'PersonalAccessToken',
    ],

    /*
    |--------------------------------------------------------------------------
    | Output Format
    |--------------------------------------------------------------------------
    |
    | The default output format for documentation generation.
    | Options: 'swagger2', 'openapi3', 'both'
    |
    */
    'default_format' => env('API_DOCS_FORMAT', 'both'),

    /*
    |--------------------------------------------------------------------------
    | Test Generation Settings
    |--------------------------------------------------------------------------
    |
    | Settings for the API test auto-generation feature.
    |
    */
    'tests' => [
        // Auto-generate authentication tests for each endpoint
        'generate_auth_tests' => true,

        // Auto-generate validation tests for POST/PUT/PATCH endpoints
        'generate_validation_tests' => true,

        // Filter for PHPUnit when running generated tests
        'phpunit_filter' => 'AutoGen',
    ],

    /*
    |--------------------------------------------------------------------------
    | Sandbox Environment
    |--------------------------------------------------------------------------
    |
    | Configure sandbox/staging environment settings for Postman.
    | These values can be overridden in your .env file.
    |
    */
    'sandbox' => [
        'url' => env('APP_SANDBOX_URL'),
        'api_key' => env('APP_SANDBOX_API_KEY'),
    ],

    /*
    |--------------------------------------------------------------------------
    | API Info
    |--------------------------------------------------------------------------
    |
    | Default information for your API documentation.
    |
    */
    'info' => [
        'title' => env('API_DOCS_TITLE'),
        'description' => env('API_DOCS_DESCRIPTION', 'API Documentation'),
        'version' => env('API_DOCS_VERSION', '1.0.0'),
        'contact' => [
            'name' => env('API_DOCS_CONTACT_NAME', 'API Support'),
            'email' => env('API_DOCS_CONTACT_EMAIL'),
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Security Definitions
    |--------------------------------------------------------------------------
    |
    | Configure the default security schemes for your API.
    |
    */
    'security' => [
        'api_key_header' => 'x-api-key',
        'access_token_header' => 'x-access-token',
    ],
];
