<?php

namespace EasyPack\ApiDocs\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Routing\Router;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Schema;
use EasyPack\ApiDocs\Docs\APICall;
use EasyPack\ApiDocs\Docs\Param;
use EasyPack\ApiDocs\Docs\ParamType;
use EasyPack\ApiDocs\Domain\FileGenerators\OpenApi\V3\OpenApiSchema;
use EasyPack\ApiDocs\Domain\FileGenerators\Postman\PostmanCollectionBuilder;
use EasyPack\ApiDocs\Domain\FileGenerators\Postman\PostmanEnvironment;
use EasyPack\ApiDocs\Domain\FileGenerators\Swagger\SwaggerV2;
use EasyPack\ApiDocs\Domain\ModelDefinition;
use EasyPack\ApiDocs\Domain\Traits\NamesAndPathLocations;
use EasyPack\ApiDocs\Domain\Vendors\ApiDoc;
use EasyPack\ApiDocs\Exceptions\DocumentationModeEnabledException;

class GenerateDocsCommand extends Command
{
    use NamesAndPathLocations;

    protected $signature = 'generate:docs
                            {--login-user-id=1 : User ID to access login of API}
                            {--login-user-pass=password : Password for the Login User}
                            {--test-user-id=1 : User ID of the test user}
                            {--no-files-output : Do not show generated files}
                            {--no-apidoc : Skip ApiDoc generation}
                            {--format=both : Output format: swagger2, openapi3, or both}
                            {--reset : Reset and start a new instance}
                            {--skip-setup-check : Skip automatic database setup verification}';

    protected $description = 'Generate API Documentation';

    protected $router;
    protected $routes;
    protected $docBuilder;
    protected $docsFolder;
    protected $createdFiles = [];
    protected $format = 'both';
    protected $allDefinitions = [];
    protected $basePath = '/api/v1';

    public function __construct(Router $router)
    {
        parent::__construct();
        $this->router = $router;
        $this->routes = $router->getRoutes();
    }

    public function handle()
    {
        // Startup checks
        if (app()->environment('production')) {
            $this->error('Application in production environment. This command cannot be run in this environment.');
            return false;
        }

        // Verify database setup before proceeding
        if (!$this->option('skip-setup-check')) {
            if (!$this->ensureDatabaseSetup()) {
                return false;
            }
        }

        $this->docsFolder = public_path('docs');
        if (!File::isDirectory($this->docsFolder)) {
            File::makeDirectory($this->docsFolder, 0755, true);
        }

        putenv('DOCUMENTATION_MODE=true');

        // Disable rate limiting during documentation generation
        app()[\Illuminate\Cache\RateLimiter::class]->clear('api');

        $this->docBuilder = app('api-docs.builder');

        if ($this->option('reset')) {
            $this->docBuilder->reset();
            $this->createdFiles = [];
        }

        $this->format = $this->option('format');
        if (!in_array($this->format, ['swagger2', 'openapi3', 'both'])) {
            $this->error("Invalid format. Use: swagger2, openapi3, or both");
            return false;
        }

        $this->info('Starting API documentation generation...');
        $this->info("Output format: {$this->format}");
        $this->info('');

        try {
            $this->defineDefaultHeaders();
            $this->hitRoutesAndLoadDocs();

            // Validate that all documented endpoints have success object defined
            if (!$this->validateSuccessObjects()) {
                return false;
            }

            $this->createDocSourceFiles();

            // Generate based on format option
            if (in_array($this->format, ['swagger2', 'both'])) {
                $this->createSwaggerJson('api');
            }

            if (in_array($this->format, ['openapi3', 'both'])) {
                $this->createOpenApiJson();
            }

            $this->createSwaggerJson('postman');
            $this->writePostmanSandboxEnvironment();

            // Compile ApiDoc HTML documentation (if not skipped)
            if (!$this->option('no-apidoc')) {
                $this->compileApiDoc();
            }

            if (!$this->option('no-files-output')) {
                $this->table(['Generated File', 'Path'], $this->createdFiles);
                $this->info('');
            }

            $this->info('✓ API documentation generated successfully!');
        } catch (\Exception $ex) {
            $this->error('Error generating documentation: ' . $ex->getMessage());
            $this->error($ex->getTraceAsString());
            return false;
        }

        putenv('DOCUMENTATION_MODE=false');
        return true;
    }

    protected function defineDefaultHeaders()
    {
        try {
            document(function () {
                return (new APICall)->setDefine('default_headers')
                    ->setHeaders([
                        (new Param('Accept', ParamType::STRING, 'Set to `application/json`'))->setDefaultValue('application/json'),
                        (new Param('x-api-key', ParamType::STRING, 'API Key')),
                        (new Param('x-access-token', ParamType::STRING, 'Unique user authentication token')),
                    ]);
            });
        } catch (DocumentationModeEnabledException $ex) {
            // Expected exception - do nothing
        }
    }

    protected function hitRoutesAndLoadDocs()
    {
        // Filter API routes
        $apiRoutes = new Collection();
        foreach ($this->routes as $route) {
            if (str_starts_with($route->uri(), 'api')) {
                $apiRoutes->push($route);
            }
        }

        $this->info("Found {$apiRoutes->count()} API routes");
        $this->info('');

        foreach ($apiRoutes as $route) {
            $routeInfo = $this->getRouteInformation($route);

            // Check if controller has @hideFromAPIDocumentation annotation
            if ($this->shouldHideFromDocumentation($routeInfo['action'])) {
                $this->line('→ ' . $routeInfo['method'] . ' ' . $routeInfo['uri']);
                $this->comment('  ○ Skipped (@hideFromAPIDocumentation)');
                continue;
            }

            // Set interceptor
            $this->docBuilder->setInterceptor(
                $routeInfo['method'],
                $routeInfo['uri'],
                $routeInfo['action']
            );

            try {
                $this->line('→ ' . $routeInfo['method'] . ' ' . $routeInfo['uri']);

                // Hit the route - in documentation mode this will register the doc
                $this->callRoute($routeInfo['method'], $routeInfo['url']);

                // If we reach here, the route isn't documented
                $this->warn('  Warning: No documentation defined');
            } catch (DocumentationModeEnabledException $ex) {
                // Expected - route has documentation
                $this->info('  ✓ Documented');
            } catch (\Exception $ex) {
                $this->error('  Error: ' . $ex->getMessage());
            }
        }

        $itemCount = $this->docBuilder->getApiCalls()->count();
        $this->info('');
        $this->info("Found {$itemCount} documented API endpoints");
        $this->info('');
    }

    /**
     * Check if a controller/method should be hidden from API documentation.
     *
     * @param string $action The action string (Controller@method)
     * @return bool
     */
    protected function shouldHideFromDocumentation(string $action): bool
    {
        if (str_contains($action, '@')) {
            [$controllerClass, $method] = explode('@', $action);
        } else {
            $controllerClass = $action;
            $method = null;
        }

        if (!class_exists($controllerClass)) {
            return false;
        }

        try {
            $reflectionClass = new \ReflectionClass($controllerClass);
            $classDocComment = $reflectionClass->getDocComment();

            // Check class-level annotation
            if ($classDocComment && str_contains($classDocComment, '@hideFromAPIDocumentation')) {
                return true;
            }

            // Check method-level annotation
            if ($method && $reflectionClass->hasMethod($method)) {
                $reflectionMethod = $reflectionClass->getMethod($method);
                $methodDocComment = $reflectionMethod->getDocComment();

                if ($methodDocComment && str_contains($methodDocComment, '@hideFromAPIDocumentation')) {
                    return true;
                }
            }
        } catch (\ReflectionException $e) {
            return false;
        }

        return false;
    }

    protected function callRoute($method, $url)
    {
        // Clear rate limiter before each request
        $rateLimiter = app(\Illuminate\Cache\RateLimiter::class);
        $rateLimiter->clear('api');

        $request = \Illuminate\Http\Request::create($url, $method);
        $request->headers->set('Accept', 'application/json');
        $request->headers->set('X-Documentation-Mode', 'true');

        if (config('app.key')) {
            $request->headers->set('x-api-key', config('app.key'));
        }

        // Add authentication for user
        $userId = $this->option('test-user-id');
        $user = \EasyPack\Models\User::find($userId);

        // User should exist due to ensureDatabaseSetup(), but create as fallback
        if (!$user) {
            $user = $this->createTestUserForDocs($userId);
        }

        // Store original request to restore later
        $originalRequest = app()->bound('request') ? app('request') : null;

        try {
            // Bind the request to the application container
            // This ensures middleware uses our test request
            app()->instance('request', $request);

            // Set the user resolver on the request to bypass authentication
            if ($user) {
                $request->setUserResolver(function () use ($user) {
                    return $user;
                });
                
                // Also set on the auth guard
                auth('sanctum')->setUser($user);
                if (auth()->getDefaultDriver() !== 'sanctum') {
                    auth()->setUser($user);
                }
            }

            $kernel = app()[\Illuminate\Contracts\Http\Kernel::class];
            $response = $kernel->handle($request);

            if ($response->exception) {
                throw $response->exception;
            }
        } finally {
            // Restore original request
            if ($originalRequest) {
                app()->instance('request', $originalRequest);
            }
        }
    }

    protected function getRouteInformation($route)
    {
        $methods = $route->methods();
        $method = count($methods) === 1 ? $methods[0] : (in_array('GET', $methods) ? 'GET' : $methods[0]);

        return [
            'method' => $method,
            'uri' => $route->uri(),
            'url' => url($route->uri()),
            'action' => ltrim($route->getActionName(), '\\'),
        ];
    }

    protected function getOutputFilePath($fileName)
    {
        return $this->docsFolder . DIRECTORY_SEPARATOR . $fileName;
    }

    /**
     * Create swagger 2.0 json file
     *
     * @param string $type
     * @throws \Exception
     */
    protected function createSwaggerJson($type = 'api'): void
    {
        if (!in_array($type, ['api', 'postman'])) {
            throw new \InvalidArgumentException("The given type $type is an invalid argument");
        }

        $items = $this->docBuilder->getApiCalls();

        if ($items->isEmpty()) {
            $this->warn('No API calls to document');
            return;
        }

        $basePath = '/api/v1';

        $modelDefinition = new ModelDefinition();
        $allDefinitions = $modelDefinition->getAllDefinitions();

        // Postman environment
        $postmanEnvironment = new PostmanEnvironment();

        // Swagger Config
        $swaggerConfig = new SwaggerV2();
        $swaggerConfig->setBasePath($basePath);
        $swaggerConfig->setServerUrl(config('app.url'));

        foreach ($items as $item) {
            /** @var APICall $item */

            $route = $item->getRoute();
            if (empty($route)) {
                continue;
            }

            $method = strtolower($item->getMethod());
            $parameters = [];

            // Get parameters
            $params = $item->getParams();
            $headers = $item->getHeaders();

            $allParams = (new Collection())->merge($headers)->merge($params);

            // Check for API use calls and merge the headers
            foreach ($item->getUse() as $useName) {
                /** @var APICall $childApiCalls */
                $childApiCalls = $this->docBuilder->findByDefinition($useName);
                if ($childApiCalls) {
                    $allParams = $allParams->merge($childApiCalls->getParams())->merge($childApiCalls->getHeaders());
                }
            }

            // Split the security tokens
            $securityDefinitions = [];
            $filteredParams = new Collection();
            foreach ($allParams as $param) {
                if ($param->getLocation() === Param::LOCATION_HEADER) {
                    $fieldName = strtolower($param->getName());
                    if (in_array($fieldName, ['x-api-key', 'x-access-token'])) {
                        if ($fieldName === 'x-api-key') {
                            $securityDefinitions[] = ['apiKey' => []];
                            continue;
                        }
                        if ($fieldName === 'x-access-token') {
                            $securityDefinitions[] = ['accessToken' => []];
                            continue;
                        }
                    }
                }

                $filteredParams->push($param);
            }

            foreach ($filteredParams as $param) {
                $dataType = $param->getDataType();
                $name = $param->getName();

                $location = $param->getLocation();
                if ($location === null) {
                    $location = $method === 'get' ? Param::LOCATION_QUERY : Param::LOCATION_FORM;
                }

                if ($dataType === 'Model' && $model = $param->getModel()) {
                    $paramData = [
                        'name' => 'body',
                        'in' => 'body',
                        'required' => $param->getRequired(),
                        'description' => $param->getDescription(),
                        'schema' => [
                            '$ref' => '#/definitions/' . ModelDefinition::getModelShortName($model),
                        ],
                    ];
                } else {
                    $paramData = [
                        'name' => $name,
                        'in' => $location,
                        'required' => $param->getRequired(),
                        'description' => $param->getDescription(),
                        'type' => strtolower($dataType),
                    ];

                    if ($paramData['type'] === Param::TYPE_ARRAY) {
                        $paramData['collectionFormat'] = $param->getCollectionFormat();
                        $paramData['items'] = $param->getItems();
                    }

                    // Set the variable slots for postman
                    if ($type === 'postman') {
                        $variable = (string)$param->getVariable();
                        if (empty($variable)) {
                            $variable = $param->getDefaultValue();
                        }
                        if (empty($variable)) {
                            $variable = $param->getExample();
                        }

                        if (!empty($variable)) {
                            if ($location === Param::LOCATION_FORM) {
                                $paramData['example'] = $variable;
                            } else {
                                $paramData['schema']['type'] = strtolower($dataType);
                                $paramData['schema']['example'] = $variable;
                            }
                        }
                    }
                }

                $parameters[] = $paramData;

                // Add variable to Environment file
                $postmanEnvironment->addVariable($name, $param->getDefaultValue());
            }

            $pathSuffix = str_replace(ltrim($basePath, '/'), '', $route);

            $consumes = $item->getConsumes();
            if (empty($consumes)) {
                $consumes[] = APICall::CONSUME_JSON;
                $consumes[] = APICall::CONSUME_FORM_URLENCODED;
            }

            // Build success responses
            $successObject = $item->getSuccessObject();
            $successPaginatedObject = $item->getSuccessPaginatedObject();
            $responseObjectName = str_replace(
                ['/', ' '],
                '',
                ucwords($item->getGroup()) . ucwords($item->getName())
            ) . 'Response';

            if ($successObject || $successPaginatedObject) {
                if ($successObject) {
                    $successResponse = $modelDefinition->getSuccessResponseDefinition($responseObjectName, $successObject);
                }

                if ($successPaginatedObject) {
                    $successResponse = $modelDefinition->getSuccessResponsePaginatedDefinition($responseObjectName, $successPaginatedObject);
                }

                if (isset($allDefinitions[$responseObjectName])) {
                    $error = "Definition $responseObjectName already exists. Change the method group or name to be unique.";
                    $this->error($error);
                    throw new \Exception($error);
                }

                $allDefinitions = array_merge($allDefinitions, $successResponse);
            } else {
                // Generic success response
                $responseObjectName = 'SuccessResponse';
            }

            $pathData = [
                'tags' => [
                    $item->getGroup(),
                ],
                'summary' => $item->getName(),
                'consumes' => $consumes,
                'produces' => ['application/json'],
                'operationId' => $item->getOperationId(),
                'description' => $item->getDescription() ?? '',
                'parameters' => $parameters,
                'security' => $securityDefinitions,
                'responses' => [
                    '200' => [
                        'schema' => [
                            '$ref' => "#/definitions/$responseObjectName",
                        ],
                        'description' => $responseObjectName,
                    ],
                    '401' => [
                        'schema' => [
                            '$ref' => '#/definitions/ApiErrorUnauthorized',
                        ],
                        'description' => 'Authentication failed',
                    ],
                    '403' => [
                        'schema' => [
                            '$ref' => '#/definitions/ApiErrorAccessDenied',
                        ],
                        'description' => 'Access denied',
                    ],
                    '422' => [
                        'schema' => [
                            '$ref' => '#/definitions/ApiError',
                        ],
                        'description' => 'Generic API error. Check `message` for more information.',
                    ],
                ],
            ];

            $swaggerConfig->addPathData($pathSuffix, $method, $pathData);
        }

        $swaggerConfig->addToSchema('definitions', $allDefinitions);

        if ($type === 'postman') {
            $this->writePostmanEnvironments($postmanEnvironment);

            // Create postman collection JSON file
            $outputPath = $this->getOutputFilePath('postman_collection.json');
            $swaggerConfig->writeOutputFileJson($outputPath);
            $this->createdFiles[] = ['Postman Collection (JSON)', $this->stripBasePath($outputPath)];

            $postmanCollection = new PostmanCollectionBuilder();
            $postmanCollection->setSchema($swaggerConfig->getSchema());
            $outputPath = $this->getOutputFilePath('postman_collection.yml');
            $postmanCollection->writeOutputFileYaml($outputPath);
            $this->createdFiles[] = ['Postman Collection (YAML)', $this->stripBasePath($outputPath)];
        } else {
            // Create swagger YAML file
            $outputPath = $this->getOutputFilePath('swagger.yml');
            $swaggerConfig->writeOutputFileYaml($outputPath);
            $this->createdFiles[] = ['Swagger v2 (YAML)', $this->stripBasePath($outputPath)];

            // Create swagger JSON file
            $outputPath = $this->getOutputFilePath('swagger.json');
            $swaggerConfig->writeOutputFileJson($outputPath);
            $this->createdFiles[] = ['Swagger v2 (JSON)', $this->stripBasePath($outputPath)];
        }
    }

    /**
     * Write Postman Environment Files
     *
     * @param PostmanEnvironment $postmanEnvironment
     */
    protected function writePostmanEnvironments(PostmanEnvironment $postmanEnvironment): void
    {
        // Generate Local Environment Config
        if (config('app.env') === 'local' || config('app.env') === 'testing') {
            $filePath = $this->docsFolder . DIRECTORY_SEPARATOR . 'postman_environment_local.json';
            $postmanEnvironment->setName(sprintf("%s Environment (LOCAL)", config('app.name')));
            $postmanEnvironment->setServerUrl(config('app.url'));

            if (config('app.key')) {
                $postmanEnvironment->addVariable('x-api-key', config('app.key'));
            }

            $postmanEnvironment->writeOutputFileJson($filePath);
            $this->createdFiles[] = ['Postman Environment (LOCAL)', $this->stripBasePath($filePath)];

            // Remove the variables after done
            $postmanEnvironment->removeVariable('x-access-token');
        } else {
            $this->error("Failed to create Local Environment file. Run this on a `local` environment.");
        }

        $this->createdFiles[] = ['---', '---'];
    }

    /**
     * Write Postman Sandbox Environment File
     */
    protected function writePostmanSandboxEnvironment(): void
    {
        $sandboxUrl = env('APP_SANDBOX_URL');

        if (empty($sandboxUrl)) {
            $this->info('  ○ APP_SANDBOX_URL not set. Skipping sandbox environment file.');
            return;
        }

        $postmanEnvironment = new PostmanEnvironment();
        $postmanEnvironment->setName(sprintf("%s Environment (SANDBOX)", config('app.name')));
        $postmanEnvironment->setServerUrl($sandboxUrl);

        // Force https on sandbox URLs
        $postmanEnvironment->addVariable('scheme', 'https');

        // Add sandbox API key if available
        $sandboxApiKey = env('APP_SANDBOX_API_KEY');
        if (!empty($sandboxApiKey)) {
            $postmanEnvironment->addVariable('x-api-key', $sandboxApiKey);
        }

        $filePath = $this->docsFolder . DIRECTORY_SEPARATOR . 'postman_environment_sandbox.json';
        $postmanEnvironment->writeOutputFileJson($filePath);
        $this->createdFiles[] = ['Postman Environment (SANDBOX)', $this->stripBasePath($filePath)];
    }

    /**
     * Create OpenAPI 3.0 specification files
     */
    protected function createOpenApiJson(): void
    {
        $items = $this->docBuilder->getApiCalls();

        if ($items->isEmpty()) {
            $this->warn('No API calls to document');
            return;
        }

        $modelDefinition = new ModelDefinition();
        $allDefinitions = $modelDefinition->getAllDefinitions();

        // OpenAPI 3.0 Config
        $openApiConfig = new OpenApiSchema();
        $openApiConfig->setBasePath($this->basePath);
        $openApiConfig->setServerUrl(config('app.url') . $this->basePath, 'Current Server');

        // Add sandbox server if configured
        $sandboxUrl = env('APP_SANDBOX_URL');
        if (!empty($sandboxUrl)) {
            $openApiConfig->addServer($sandboxUrl . $this->basePath, 'Sandbox Server');
        }

        foreach ($items as $item) {
            /** @var APICall $item */

            $route = $item->getRoute();
            if (empty($route)) {
                continue;
            }

            $method = strtolower($item->getMethod());
            $parameters = [];

            // Get parameters
            $params = $item->getParams();
            $headers = $item->getHeaders();

            $allParams = (new Collection())->merge($headers)->merge($params);

            // Check for API use calls and merge the headers
            foreach ($item->getUse() as $useName) {
                $childApiCalls = $this->docBuilder->findByDefinition($useName);
                if ($childApiCalls) {
                    $allParams = $allParams->merge($childApiCalls->getParams())->merge($childApiCalls->getHeaders());
                }
            }

            // Split the security tokens
            $securityDefinitions = [];
            $filteredParams = new Collection();
            foreach ($allParams as $param) {
                if ($param->getLocation() === Param::LOCATION_HEADER) {
                    $fieldName = strtolower($param->getName());
                    if (in_array($fieldName, ['x-api-key', 'x-access-token'])) {
                        if ($fieldName === 'x-api-key') {
                            $securityDefinitions[] = ['apiKey' => []];
                            continue;
                        }
                        if ($fieldName === 'x-access-token') {
                            $securityDefinitions[] = ['accessToken' => []];
                            continue;
                        }
                    }
                }

                $filteredParams->push($param);
            }

            foreach ($filteredParams as $param) {
                $dataType = $param->getDataType();
                $name = $param->getName();

                $location = $param->getLocation();
                if ($location === null) {
                    $location = $method === 'get' ? Param::LOCATION_QUERY : Param::LOCATION_FORM;
                }

                if ($dataType === 'Model' && $model = $param->getModel()) {
                    $paramData = [
                        'name' => 'body',
                        'in' => 'body',
                        'required' => $param->getRequired(),
                        'description' => $param->getDescription(),
                        'schema' => [
                            '$ref' => '#/definitions/' . ModelDefinition::getModelShortName($model),
                        ],
                    ];
                } else {
                    $paramData = [
                        'name' => $name,
                        'in' => $location,
                        'required' => $param->getRequired(),
                        'description' => $param->getDescription(),
                        'type' => strtolower($dataType),
                    ];

                    if ($paramData['type'] === Param::TYPE_ARRAY) {
                        $paramData['collectionFormat'] = $param->getCollectionFormat();
                        $paramData['items'] = $param->getItems();
                    }
                }

                $parameters[] = $paramData;
            }

            $pathSuffix = str_replace(ltrim($this->basePath, '/'), '', $route);

            $consumes = $item->getConsumes();
            if (empty($consumes)) {
                $consumes[] = APICall::CONSUME_JSON;
                $consumes[] = APICall::CONSUME_FORM_URLENCODED;
            }

            // Build success responses
            $successObject = $item->getSuccessObject();
            $successPaginatedObject = $item->getSuccessPaginatedObject();
            $responseObjectName = str_replace(
                ['/', ' '],
                '',
                ucwords($item->getGroup()) . ucwords($item->getName())
            ) . 'Response';

            if ($successObject || $successPaginatedObject) {
                if ($successObject) {
                    $successResponse = $modelDefinition->getSuccessResponseDefinition($responseObjectName, $successObject);
                }

                if ($successPaginatedObject) {
                    $successResponse = $modelDefinition->getSuccessResponsePaginatedDefinition($responseObjectName, $successPaginatedObject);
                }

                if (!isset($allDefinitions[$responseObjectName])) {
                    $allDefinitions = array_merge($allDefinitions, $successResponse);
                }
            } else {
                $responseObjectName = 'SuccessResponse';
            }

            $pathData = [
                'tags' => [$item->getGroup()],
                'summary' => $item->getName(),
                'consumes' => $consumes,
                'produces' => ['application/json'],
                'operationId' => $item->getOperationId(),
                'description' => $item->getDescription() ?? '',
                'parameters' => $parameters,
                'security' => $securityDefinitions,
                'responses' => [
                    '200' => [
                        'schema' => ['$ref' => "#/definitions/$responseObjectName"],
                        'description' => $responseObjectName,
                    ],
                    '401' => [
                        'schema' => ['$ref' => '#/definitions/ApiErrorUnauthorized'],
                        'description' => 'Authentication failed',
                    ],
                    '403' => [
                        'schema' => ['$ref' => '#/definitions/ApiErrorAccessDenied'],
                        'description' => 'Access denied',
                    ],
                    '422' => [
                        'schema' => ['$ref' => '#/definitions/ApiError'],
                        'description' => 'Generic API error. Check `message` for more information.',
                    ],
                ],
            ];

            $openApiConfig->addPathData($pathSuffix, $method, $pathData);
        }

        $openApiConfig->addToSchema('definitions', $allDefinitions);

        // Create OpenAPI 3.0 YAML file
        $outputPath = $this->getOutputFilePath('openapi.yml');
        $openApiConfig->writeOutputFileYaml($outputPath);
        $this->createdFiles[] = ['OpenAPI 3.0 (YAML)', $this->stripBasePath($outputPath)];

        // Create OpenAPI 3.0 JSON file
        $outputPath = $this->getOutputFilePath('openapi.json');
        $openApiConfig->writeOutputFileJson($outputPath);
        $this->createdFiles[] = ['OpenAPI 3.0 (JSON)', $this->stripBasePath($outputPath)];
    }

    /**
     * Create the documentation source files
     */
    protected function createDocSourceFiles(): void
    {
        $items = $this->docBuilder->getApiCalls();

        if ($items->isEmpty()) {
            return;
        }

        $docsFolder = self::getApiDocsAutoGenDir(true);

        self::deleteFilesInDirectory($docsFolder, 'coffee');

        foreach ($items as $item) {
            /** @var APICall $item */
            $outputFile = \Illuminate\Support\Str::snake($item->getGroup() . '.coffee');
            $outputPath = $docsFolder . DIRECTORY_SEPARATOR . $outputFile;

            $lines = [];
            $lines[] = "# ******************************************************** #";
            $lines[] = "#           AUTO-GENERATED. DO NOT EDIT THIS FILE.         #";
            $lines[] = "# ******************************************************** #";
            $lines[] = "#    Create your files in `resources/docs/apidoc/manual`   #";
            $lines[] = "# ******************************************************** #";
            $lines[] = $item->getApiDoc();
            $lines[] = '';
            file_put_contents($outputPath, implode("\r\n", $lines), FILE_APPEND);
        }

        $this->createdFiles[] = ['APIDoc Files', $this->stripBasePath($docsFolder)];
        $this->createdFiles[] = ['---', '---'];
    }

    protected function stripBasePath($path)
    {
        return str_replace(base_path(), '', $path);
    }

    /**
     * Validate that all documented API calls have setSuccessObject, setSuccessPaginatedObject, or setSuccessMessageOnly defined.
     *
     * @return bool Returns true if all validations pass, false otherwise
     */
    protected function validateSuccessObjects(): bool
    {
        $items = $this->docBuilder->getApiCalls();
        $errors = [];

        foreach ($items as $item) {
            /** @var APICall $item */

            // Skip definitions (they don't need success objects)
            if (!empty($item->getDefine())) {
                continue;
            }

            $successObject = $item->getSuccessObject();
            $successPaginatedObject = $item->getSuccessPaginatedObject();
            $isMessageOnly = $item->isSuccessMessageOnly();

            // Check if none of the success response types are set
            if (empty($successObject) && empty($successPaginatedObject) && !$isMessageOnly) {
                $route = $item->getRoute() ?? 'Unknown route';
                $method = $item->getMethod() ?? 'Unknown method';
                $group = $item->getGroup() ?? 'Unknown group';
                $name = $item->getName() ?? 'Unknown name';

                $errors[] = [
                    'endpoint' => strtoupper($method) . ' ' . $route,
                    'group' => $group,
                    'name' => $name,
                ];
            }
        }

        if (!empty($errors)) {
            $this->error('');
            $this->error('╔══════════════════════════════════════════════════════════════════════════════╗');
            $this->error('║  Missing success response definition in document() function                  ║');
            $this->error('╚══════════════════════════════════════════════════════════════════════════════╝');
            $this->error('');

            $this->table(
                ['Endpoint', 'Group', 'Name'],
                array_map(fn($e) => [$e['endpoint'], $e['group'], $e['name']], $errors)
            );

            $this->error('');
            $this->error('Please add one of the following to each document() function listed above:');
            $this->error('  • setSuccessObject(Model::class)           - For endpoints returning a single model');
            $this->error('  • setSuccessPaginatedObject(Model::class)  - For endpoints returning paginated results');
            $this->error('  • setSuccessMessageOnly()                  - For endpoints returning only a message (e.g., logout, delete)');
            $this->error('');

            return false;
        }

        $this->info('✓ All documented endpoints have success response definitions');
        return true;
    }

    /**
     * Compile ApiDoc HTML documentation
     */
    protected function compileApiDoc(): void
    {
        $this->info('');
        $this->info('Compiling ApiDoc HTML documentation...');

        if (!ApiDoc::isInstalled()) {
            $this->error('  ✗ ApiDoc.js is not installed!');
            $this->line(ApiDoc::getInstallInstructions());
            return;
        }

        try {
            $process = ApiDoc::compile();
            
            if ($process->isSuccessful()) {
                $this->info('  ✓ ApiDoc HTML compiled successfully');
                $this->createdFiles[] = ['API Docs (HTML)', $this->stripBasePath(self::getApiDocsOutputDir())];
            } else {
                $this->warn('  ⚠ ApiDoc compilation had warnings: ' . $process->getErrorOutput());
            }
        } catch (\Exception $e) {
            $this->warn('  ⚠ ApiDoc compilation failed: ' . $e->getMessage());
        }
    }

    /**
     * Ensure database is properly set up for documentation generation.
     * Automatically runs migrations and seeders if needed.
     *
     * @return bool True if setup is complete, false if there was an error
     */
    protected function ensureDatabaseSetup(): bool
    {
        $this->info('Checking database setup...');

        // Step 1: Check database connection
        try {
            $hasMigrations = Schema::hasTable('users');
        } catch (\Exception $e) {
            $this->error('');
            $this->error('╔══════════════════════════════════════════════════════════════════════════════╗');
            $this->error('║  Database connection failed!                                                 ║');
            $this->error('╚══════════════════════════════════════════════════════════════════════════════╝');
            $this->error('');
            $this->line('  <fg=red>Error:</> ' . $e->getMessage());
            $this->line('');
            $this->line('  Please check your database configuration in <fg=yellow>.env</> file:');
            $this->line('    DB_CONNECTION, DB_HOST, DB_PORT, DB_DATABASE, DB_USERNAME, DB_PASSWORD');
            $this->line('');
            $this->line('  Quick fix options:');
            $this->line('    1. Run <fg=yellow>php artisan oxygen:install</> for guided setup');
            $this->line('    2. Or fix .env manually and run <fg=yellow>php artisan migrate</>');
            $this->line('');
            return false;
        }

        // Step 2: Run migrations if tables don't exist
        if (!$hasMigrations) {
            $this->warn('  ⚠ Database tables not found. Running migrations...');
            
            try {
                Artisan::call('migrate', ['--force' => true], $this->output);
                $this->info('  ✓ Migrations completed');
            } catch (\Exception $e) {
                $this->error('  ✗ Migration failed: ' . $e->getMessage());
                $this->line('    Run <fg=yellow>php artisan migrate</> manually to see full error.');
                return false;
            }
        }

        // Step 3: Check if Spatie Permission tables exist
        $hasRoles = Schema::hasTable('roles');
        if (!$hasRoles) {
            $this->warn('  ⚠ Roles table not found. Setting up Spatie Permission...');
            
            try {
                // Publish Spatie migrations if not published
                Artisan::call('vendor:publish', [
                    '--provider' => 'Spatie\\Permission\\PermissionServiceProvider',
                    '--tag' => 'permission-migrations',
                ], $this->output);
                
                Artisan::call('migrate', ['--force' => true], $this->output);
                $this->info('  ✓ Permission tables created');
            } catch (\Exception $e) {
                $this->error('  ✗ Permission migration failed: ' . $e->getMessage());
                $this->line('    Run <fg=yellow>php artisan vendor:publish --provider="Spatie\\Permission\\PermissionServiceProvider"</> manually.');
                return false;
            }
        }

        // Step 4: Check if roles have been seeded
        try {
            $roleCount = \Spatie\Permission\Models\Role::count();
        } catch (\Exception $e) {
            $roleCount = 0;
        }

        if ($roleCount === 0) {
            $this->warn('  ⚠ No roles found. Running Oxygen seeders...');
            $this->line('    <fg=gray>This will create roles, permissions, settings, and test users.</>');
            
            try {
                Artisan::call('db:seed', [
                    '--class' => 'Oxygen\\Starter\\Database\\Seeders\\OxygenSeeder',
                    '--force' => true,
                ], $this->output);
                $this->info('  ✓ Database seeded successfully');
                $this->line('    <fg=gray>Test users: test@example.com, admin@example.com, superadmin@example.com</>');
                $this->line('    <fg=gray>Password for all: password</>');
            } catch (\Exception $e) {
                $this->error('  ✗ Seeding failed: ' . $e->getMessage());
                $this->line('');
                $this->line('  Run manually:');
                $this->line('    <fg=yellow>php artisan db:seed --class="Oxygen\\Starter\\Database\\Seeders\\OxygenSeeder"</>');
                $this->line('');
                return false;
            }
        }

        // Step 5: Check if test user exists
        $userId = $this->option('test-user-id');
        $userExists = \EasyPack\Models\User::find($userId);
        
        if (!$userExists) {
            $this->warn("  ⚠ Test user (ID: {$userId}) not found. Creating...");
            
            try {
                $user = $this->createTestUserForDocs($userId);
                $this->info("  ✓ Test user created: {$user->email}");
            } catch (\Exception $e) {
                $this->error('  ✗ Could not create test user: ' . $e->getMessage());
                return false;
            }
        }

        $this->info('  ✓ Database setup verified');
        $this->info('');
        
        return true;
    }

    /**
     * Create a test user for documentation generation.
     * The user is assigned admin role to ensure all API endpoints can be documented.
     *
     * @param int $userId
     * @return \EasyPack\Models\User
     */
    protected function createTestUserForDocs(int $userId): \EasyPack\Models\User
    {
        $user = \EasyPack\Models\User::create([
            'id' => $userId,
            'name' => 'Documentation Test User',
            'email' => "doctest{$userId}@example.com",
            'password' => bcrypt('password'),
            'email_verified_at' => now(),
        ]);

        // Assign admin role - required for documenting protected endpoints
        try {
            $superAdminRoleExists = \Spatie\Permission\Models\Role::where('name', 'super-admin')->exists();
            $adminRoleExists = \Spatie\Permission\Models\Role::where('name', 'admin')->exists();
            
            if ($superAdminRoleExists) {
                if (method_exists($user, 'syncRoles')) {
                    $user->syncRoles(['super-admin']);
                } elseif (method_exists($user, 'assignRole')) {
                    $user->assignRole('super-admin');
                }
            } elseif ($adminRoleExists) {
                if (method_exists($user, 'syncRoles')) {
                    $user->syncRoles(['admin']);
                } elseif (method_exists($user, 'assignRole')) {
                    $user->assignRole('admin');
                }
            }
        } catch (\Exception $e) {
            // Role assignment failed, but user is created
            // This may cause some endpoints to fail documentation, but won't stop the process
        }

        return $user;
    }
}
