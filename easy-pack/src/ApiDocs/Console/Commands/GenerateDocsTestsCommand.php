<?php

namespace EasyPack\ApiDocs\Console\Commands;

use Illuminate\Console\Command;
use EasyPack\ApiDocs\Domain\Traits\NamesAndPathLocations;
use EasyPack\ApiDocs\Domain\Vendors\ApiDoc;
use Symfony\Component\Process\Process;

class GenerateDocsTestsCommand extends Command
{
    use NamesAndPathLocations;

    protected $signature = 'generate:docs-tests
                            {--login-user-id=1 : User ID to access login of API}
                            {--login-user-pass=password : Password for the Login User}
                            {--test-user-id=1 : User ID of the test user}
                            {--no-apidoc : Skip ApiDoc generation}
                            {--force-tests : Overwrite existing test files}
                            {--filter= : Filter for PHPUnit tests}';

    protected $description = 'Generate API Documentation, API Tests, and Run Tests';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle(): int
    {
        $this->info('╔════════════════════════════════════════════════════════════╗');
        $this->info('║           API Documentation & Tests Generator              ║');
        $this->info('╚════════════════════════════════════════════════════════════╝');
        $this->info('');

        // Step 1: Generate initial docs
        $this->info('Step 1: Generating API documentation...');
        $this->info('');

        $result = $this->call('generate:docs', [
            '--login-user-id' => $this->option('login-user-id'),
            '--login-user-pass' => $this->option('login-user-pass'),
            '--test-user-id' => $this->option('test-user-id'),
            '--no-apidoc' => true,  // Skip ApiDoc on first pass
            '--no-files-output' => true,
            '--reset' => true,
        ]);

        if ($result !== 0) {
            $this->error('Documentation generation failed. Aborting...');
            return 1;
        }

        $this->info('');

        // Step 2: Generate tests from the documentation
        $this->info('Step 2: Generating API tests...');
        $this->info('');

        $testOptions = [];
        if ($this->option('force-tests')) {
            $testOptions['--force'] = true;
        }

        $result = $this->call('generate:api-tests', $testOptions);

        if ($result !== 0) {
            $this->warn('Test generation had some issues. Continuing...');
        }

        $this->info('');

        // Step 3: Clear old API responses
        $this->info('Step 3: Clearing old API responses...');

        $dirPath = self::getApiResponsesAutoGenDir();
        if (is_dir($dirPath)) {
            self::deleteFilesInDirectory($dirPath, 'json');
            $this->info('  ✓ Cleared old API response files');
        }

        $this->info('');

        // Step 4: Run the tests to capture responses
        $this->info('Step 4: Running API tests to capture responses...');
        $this->info('');

        putenv('DOCUMENTATION_MODE=false');

        $filter = $this->option('filter') ?: 'AutoGen';
        $phpunitPath = base_path('vendor/bin/phpunit');

        $process = Process::fromShellCommandline("{$phpunitPath} --filter {$filter}");
        $process->setTimeout(300);  // 5 minutes timeout
        $process->run(function ($type, $buffer) {
            $this->output->write($buffer);
        });

        $this->info('');

        // Step 5: Regenerate docs with responses
        $this->info('Step 5: Regenerating documentation with test responses...');
        $this->info('');

        $this->call('generate:docs', [
            '--login-user-id' => $this->option('login-user-id'),
            '--login-user-pass' => $this->option('login-user-pass'),
            '--test-user-id' => $this->option('test-user-id'),
            '--no-apidoc' => $this->option('no-apidoc'),
            '--reset' => true,
        ]);

        // Step 6: Compile ApiDoc (if installed and enabled)
        if (!$this->option('no-apidoc')) {
            $this->info('');
            $this->info('Step 6: Compiling ApiDoc...');

            if (ApiDoc::isInstalled()) {
                try {
                    $process = ApiDoc::compile();
                    $this->info($process->getOutput());
                    $this->info('  ✓ ApiDoc compiled successfully');
                } catch (\Exception $e) {
                    $this->warn('  ⚠ ApiDoc compilation failed: ' . $e->getMessage());
                }
            } else {
                $this->info('  ○ ApiDoc not installed. Skipping.');
                $this->line(ApiDoc::getInstallInstructions());
            }
        }

        $this->info('');
        $this->info('╔════════════════════════════════════════════════════════════╗');
        $this->info('║             ✓ All steps completed successfully!            ║');
        $this->info('╚════════════════════════════════════════════════════════════╝');
        $this->info('');

        $this->table(
            ['Output', 'Location'],
            [
                ['Swagger 2.0', 'public/docs/swagger.json'],
                ['Swagger YAML', 'public/docs/swagger.yml'],
                ['Postman Collection', 'public/docs/postman_collection.json'],
                ['Postman Environment', 'public/docs/postman_environment_local.json'],
                ['Swagger UI', 'public/docs/swagger.html'],
                ['Auto-gen Tests', 'tests/Feature/AutoGen/API/'],
                ['API Responses', 'resources/docs/api_responses/auto_generated/'],
            ]
        );

        return 0;
    }
}
