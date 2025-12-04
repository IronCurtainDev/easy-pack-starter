<?php

namespace EasyPack\Http\Controllers\Manage;

use EasyPack\Http\Controllers\Controller;

class ManageDocumentationController extends Controller
{
    public function index()
    {
        $apiKeys = array_filter(explode(',', env('API_KEY', '')));

        $filteredPaths = [];

        $files = [
            [
                'name' => 'API Documentation',
                'file_path' => '/docs/api/index.html',
                'description' => 'Interactive API documentation with examples',
            ],
            [
                'name' => 'Swagger UI Loader',
                'file_path' => '/docs/swagger.html',
                'description' => 'Swagger UI for testing API endpoints',
            ],
            [
                'name' => 'OpenAPI Specification (JSON)',
                'file_path' => '/docs/openapi.json',
                'description' => 'OpenAPI 3.0 specification file',
            ],
            [
                'name' => 'OpenAPI Specification (YAML)',
                'file_path' => '/docs/openapi.yml',
                'description' => 'OpenAPI 3.0 specification file',
            ],
            [
                'name' => 'Swagger API Specification (JSON)',
                'file_path' => '/docs/swagger.json',
                'description' => 'Swagger 2.0 specification file',
            ],
            [
                'name' => 'Swagger API Specification (YAML)',
                'file_path' => '/docs/swagger.yml',
                'description' => 'Swagger 2.0 specification file',
            ],
            [
                'name' => 'Postman Collection File (JSON)',
                'file_path' => '/docs/postman_collection.json',
                'description' => 'Import this file directly to Postman for testing',
            ],
            [
                'name' => 'Postman Collection File (YAML)',
                'file_path' => '/docs/postman_collection.yml',
                'description' => 'Import this file directly to Postman for testing',
            ],
            [
                'name' => 'Postman Environment File',
                'file_path' => '/docs/postman_environment.json',
                'description' => 'Postman environment variables for the collection',
            ],
            [
                'name' => 'Postman (LOCAL) Environment File',
                'file_path' => '/docs/postman_environment_local.json',
                'description' => 'Postman LOCAL environment variables for the collection',
            ],
            [
                'name' => 'Postman (SANDBOX) Environment File',
                'file_path' => '/docs/postman_environment_sandbox.json',
                'description' => 'Postman SANDBOX environment variables for the collection',
            ],
        ];

        foreach ($files as $file) {
            if (file_exists(public_path($file['file_path']))) {
                $filteredPaths[] = $file;
            }
        }

        return view('easypack::manage.documentation.index', [
            'pageTitle' => 'API Documentation',
            'paths' => $filteredPaths,
            'apiKeys' => $apiKeys,
        ]);
    }
}
