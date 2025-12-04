<?php

namespace EasyPack\ApiDocs\Console\Commands;

use Illuminate\Console\GeneratorCommand;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;
use EasyPack\ApiDocs\Domain\FileGenerators\Postman\PostmanCollectionReader;
use EasyPack\ApiDocs\Domain\Traits\NamesAndPathLocations;
use EasyPack\ApiDocs\Domain\Vendors\CsFixer;

class GenerateApiTestsCommand extends GeneratorCommand
{
    use NamesAndPathLocations;

    protected $signature = 'generate:api-tests {--force : Force overwrite existing test files} {--debug : Dump debug information} {--source=swagger : Source file to read (swagger, postman)}';

    protected $description = 'Generate API Tests from documented endpoints';

    protected $type = 'Test';

    protected string $pathVersion = 'v1';

    public function handle(): int
    {
        $this->info('Generating API tests from documentation...');
        $this->info('');

        try {
            $reader = new PostmanCollectionReader();
            $reader->loadFromJson();
            $pathsData = $reader->getPathData();
            $this->pathVersion = $reader->getPathVersion();
        } catch (\RuntimeException $e) {
            $this->error($e->getMessage());
            $this->error('Please run `php artisan generate:docs` first to create the documentation files.');
            return 1;
        }

        if (empty($pathsData)) {
            $this->error('No API endpoints found in documentation.');
            return 1;
        }

        $this->info("Found " . count($pathsData) . " API endpoints.");
        $this->info('');

        $this->generateApiBaseTestCase();

        $generatedCount = 0;
        $skippedCount = 0;

        foreach ($pathsData as $pathData) {
            $result = $this->generateApiTest($pathData);
            if ($result === true) {
                $generatedCount++;
            } elseif ($result === false) {
                $skippedCount++;
            }
        }

        $this->info('');
        $this->info("Generated: {$generatedCount} tests");
        if ($skippedCount > 0) {
            $this->info("Skipped: {$skippedCount} tests (manual versions exist or already generated)");
        }

        $this->fixCodeStyle();

        $this->info('');
        $this->info('API test generation completed!');

        return 0;
    }

    protected function generateApiBaseTestCase(): bool
    {
        $name = 'APIBaseTestCase';
        $path = base_path('tests/Feature/AutoGen/API/APIBaseTestCase.php');

        File::ensureDirectoryExists(dirname($path));

        $stubPath = $this->resolveStubPath('/stubs/tests/APIBaseTestCase.stub');
        $contents = file_get_contents($stubPath);

        file_put_contents($path, $contents);
        $this->info('-> Generated APIBaseTestCase');

        return true;
    }

    protected function generateApiTest(array $pathData): ?bool
    {
        $className = $this->getClassNameFromPathData($pathData);
        $namespace = 'Tests\\Feature\\AutoGen\\API\\' . strtoupper($this->pathVersion);
        $path = base_path('tests/Feature/AutoGen/API/' . strtoupper($this->pathVersion) . '/' . $className . '.php');

        $manualPath = str_replace('AutoGen', 'Manual', $path);
        if (file_exists($manualPath)) {
            $this->line("  o Skipped {$className} (manual version exists)");
            return false;
        }

        if (file_exists($path) && !$this->option('force')) {
            $this->line("  o Skipped {$className} (use --force to overwrite)");
            return false;
        }

        File::ensureDirectoryExists(dirname($path));

        try {
            $content = $this->buildTestClass($className, $namespace, $pathData);
            file_put_contents($path, $content);
            $this->info("  + Generated {$className}");
            return true;
        } catch (\Exception $e) {
            $this->error("  x Failed to generate {$className}: " . $e->getMessage());
            if ($this->option('debug')) {
                $this->error($e->getTraceAsString());
            }
            return null;
        }
    }

    protected function buildTestClass(string $className, string $namespace, array $pathData): string
    {
        $method = $pathData['method'];
        $uri = $pathData['uri'];
        $summary = $pathData['summary'] ?: $uri;
        $group = $pathData['tags'][0] ?? 'API';
        $testName = $this->getTestNameFromPathData($pathData);

        $pathSuffix = preg_replace('#^/api/v\d+#', '', $uri);
        if (empty($pathSuffix)) {
            $pathSuffix = '/';
        }

        $requestBody = $this->buildRequestBody($pathData['parameters'] ?? []);
        $queryParams = $this->buildQueryParams($pathData['parameters'] ?? []);
        $responseStructure = $this->buildResponseStructure($pathData['responses'] ?? []);

        $responseFileName = Str::snake($group . '_' . $summary);
        $responseFileName = preg_replace('/[^a-z0-9_]/', '', $responseFileName);

        $pathVersion = $this->pathVersion;
        $ucMethod = $this->ucfirstMethod($method);
        $jsonMethod = $this->getJsonMethodName($method);
        $extraParam = in_array($method, ['POST', 'PUT', 'PATCH']) ? ', []' : '';

        $lines = [];
        $lines[] = '<?php';
        $lines[] = '';
        $lines[] = "namespace {$namespace};";
        $lines[] = '';
        $lines[] = 'use Tests\Feature\AutoGen\API\APIBaseTestCase;';
        $lines[] = '';
        $lines[] = '/**';
        $lines[] = ' * Auto-generated API Test';
        $lines[] = ' *';
        $lines[] = ' * AUTO-GENERATED. DO NOT EDIT THIS FILE.';
        $lines[] = ' * If you need to customize this test, copy it to:';
        $lines[] = " * tests/Feature/Manual/API/{$pathVersion}/{$className}.php";
        $lines[] = ' * The auto-generator will skip tests that have a Manual copy.';
        $lines[] = ' *';
        $lines[] = " * Endpoint: {$method} {$uri}";
        $lines[] = " * Group: {$group}";
        $lines[] = " * Summary: {$summary}";
        $lines[] = ' *';
        $lines[] = " * @package {$namespace}";
        $lines[] = ' */';
        $lines[] = "class {$className} extends APIBaseTestCase";
        $lines[] = '{';
        $lines[] = '    /**';
        $lines[] = "     * Test {$summary}";
        $lines[] = '     *';
        $lines[] = '     * @return void';
        $lines[] = '     */';
        $lines[] = "    public function {$testName}(): void";
        $lines[] = '    {';

        $lines[] = $this->buildRequestLine($method, $pathSuffix, $requestBody, $queryParams);

        $lines[] = '';
        $lines[] = '        // Assert successful response';
        $lines[] = '        $this->assertApiSuccess($response);';
        if (!empty($responseStructure)) {
            $lines[] = $responseStructure;
        }
        $lines[] = '';
        $lines[] = '        // Save response for documentation';
        $lines[] = "        \$this->saveResponseToFile(\$response, '{$responseFileName}');";
        $lines[] = '    }';

        if (in_array($method, ['POST', 'PUT', 'PATCH'])) {
            $lines[] = '';
            $lines[] = '    /**';
            $lines[] = "     * Test {$summary} with invalid data";
            $lines[] = '     *';
            $lines[] = '     * @return void';
            $lines[] = '     */';
            $lines[] = "    public function {$testName}_validation_error(): void";
            $lines[] = '    {';
            $lines[] = "        \$response = \$this->api{$ucMethod}('{$pathSuffix}', []);";
            $lines[] = '';
            $lines[] = '        // Assert validation error (if endpoint requires data)';
            $lines[] = '        // $this->assertValidationError($response);';
            $lines[] = '        $response->assertStatus(422);';
            $lines[] = '    }';
        }

        $lines[] = '';
        $lines[] = '    /**';
        $lines[] = "     * Test {$summary} without authentication";
        $lines[] = '     *';
        $lines[] = '     * @return void';
        $lines[] = '     */';
        $lines[] = "    public function {$testName}_unauthenticated(): void";
        $lines[] = '    {';
        $lines[] = "        \$this->app['auth']->forgetGuards();";
        $lines[] = '';
        $lines[] = "        \$response = \$this->{$jsonMethod}(\$this->apiBasePath . '{$pathSuffix}'{$extraParam});";
        $lines[] = '';
        $lines[] = '        // Assert unauthorized (comment out if endpoint is public)';
        $lines[] = '        // $this->assertUnauthorized($response);';
        $lines[] = '        $response->assertStatus(401);';
        $lines[] = '    }';
        $lines[] = '}';
        $lines[] = '';

        return implode("\n", $lines);
    }

    protected function buildRequestLine(string $method, string $pathSuffix, string $requestBody, string $queryParams): string
    {
        return match ($method) {
            'GET' => !empty($queryParams) && $queryParams !== '[]'
                ? "        \$response = \$this->apiGet('{$pathSuffix}', {$queryParams});"
                : "        \$response = \$this->apiGet('{$pathSuffix}');",
            'POST' => "        \$response = \$this->apiPost('{$pathSuffix}', {$requestBody});",
            'PUT' => "        \$response = \$this->apiPut('{$pathSuffix}', {$requestBody});",
            'PATCH' => "        \$response = \$this->apiPatch('{$pathSuffix}', {$requestBody});",
            'DELETE' => "        \$response = \$this->apiDelete('{$pathSuffix}');",
            default => "        \$response = \$this->apiRequest('{$method}', '{$pathSuffix}');",
        };
    }

    protected function buildRequestBody(array $parameters): string
    {
        $body = [];

        foreach ($parameters as $param) {
            $location = $param['in'] ?? 'query';
            if (!in_array($location, ['body', 'formData'])) {
                continue;
            }

            $name = $param['name'] ?? '';
            if (empty($name) || $name === 'body') {
                continue;
            }

            $type = $param['type'] ?? 'string';
            $example = $param['example'] ?? $param['schema']['example'] ?? null;

            $body[$name] = $this->getSampleValueString($type, $name, $example);
        }

        if (empty($body)) {
            return '[]';
        }

        return $this->arrayToCode($body);
    }

    protected function buildQueryParams(array $parameters): string
    {
        $params = [];

        foreach ($parameters as $param) {
            $location = $param['in'] ?? 'query';
            if ($location !== 'query') {
                continue;
            }

            $name = $param['name'] ?? '';
            if (empty($name)) {
                continue;
            }

            $type = $param['type'] ?? 'string';
            $example = $param['example'] ?? $param['schema']['example'] ?? null;

            $params[$name] = $this->getSampleValueString($type, $name, $example);
        }

        if (empty($params)) {
            return '[]';
        }

        return $this->arrayToCode($params);
    }

    protected function buildResponseStructure(array $responses): string
    {
        if (!isset($responses['200'])) {
            return '';
        }

        return "        // \$response->assertJsonStructure(['data']);";
    }

    protected function getSampleValueString(string $type, string $name, $example = null): string
    {
        if ($example !== null) {
            if (is_string($example)) {
                return "'{$example}'";
            }
            return (string) $example;
        }

        $nameLower = strtolower($name);

        return match (true) {
            str_contains($nameLower, 'email') => "fake()->email()",
            str_contains($nameLower, 'password') => "'Password123!'",
            str_contains($nameLower, 'name') => "fake()->name()",
            str_contains($nameLower, 'phone') => "fake()->phoneNumber()",
            str_contains($nameLower, 'id') && $type === 'integer' => "1",
            $type === 'integer' || $type === 'number' => "fake()->numberBetween(1, 100)",
            $type === 'boolean' => "true",
            $type === 'array' => "[]",
            default => "fake()->word()",
        };
    }

    protected function arrayToCode(array $array): string
    {
        $parts = [];
        foreach ($array as $key => $value) {
            $parts[] = "'{$key}' => {$value}";
        }
        return "[\n            " . implode(",\n            ", $parts) . ",\n        ]";
    }

    protected function getClassNameFromPathData(array $pathData): string
    {
        $name = '';

        if (!empty($pathData['tags'][0])) {
            $name .= $pathData['tags'][0];
        }

        if (!empty($pathData['summary'])) {
            $name .= $pathData['summary'];
        }

        $name .= 'APITest';
        $name = str_replace([' ', '-', '/', '.'], '', $name);
        $name = preg_replace('/[^a-zA-Z0-9]/', '', $name);

        return $name;
    }

    protected function getTestNameFromPathData(array $pathData): string
    {
        $parts = ['test', 'api'];

        if (!empty($pathData['operationId'])) {
            $parts[] = $pathData['operationId'];
        } else {
            $method = strtolower($pathData['method']);
            $uri = $pathData['uri'];
            $parts[] = $method;
            $parts[] = preg_replace('/[^a-z0-9]/', '_', strtolower($uri));
        }

        return strtolower(implode('_', array_filter($parts)));
    }

    protected function getJsonMethodName(string $method): string
    {
        return match ($method) {
            'GET' => 'getJson',
            'POST' => 'postJson',
            'PUT' => 'putJson',
            'PATCH' => 'patchJson',
            'DELETE' => 'deleteJson',
            default => 'json',
        };
    }

    protected function ucfirstMethod(string $method): string
    {
        return ucfirst(strtolower($method));
    }

    protected function fixCodeStyle(): void
    {
        if (!CsFixer::isInstalled()) {
            $this->warn('PHP-CS-Fixer not found. Skipping code style cleanup.');
            return;
        }

        $path = base_path('tests/Feature/AutoGen/API');
        if (is_dir($path)) {
            $this->info('Fixing code style...');
            CsFixer::fix($path);
        }
    }

    protected function resolveStubPath(string $stub): string
    {
        $customPath = $this->laravel->basePath(trim($stub, '/'));

        return file_exists($customPath)
            ? $customPath
            : __DIR__ . $stub;
    }

    protected function getStub(): string
    {
        return $this->resolveStubPath('/stubs/tests/test.stub');
    }
}
