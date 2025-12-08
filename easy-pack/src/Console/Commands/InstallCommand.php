<?php

namespace EasyPack\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Str;

class InstallCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'easypack:install
                            {--force : Overwrite existing files}
                            {--quick : Quick install with MySQL defaults (minimal prompts)}
                            {--skip-migrations : Skip running migrations}
                            {--skip-seeders : Skip running seeders}
                            {--skip-controllers : Skip publishing all controllers}
                            {--skip-admin-controllers : Skip publishing admin controllers}
                            {--skip-auth-controllers : Skip publishing web auth controllers}
                            {--skip-routes : Skip publishing and configuring routes}
                            {--with-docs : Generate API documentation after installation}
                            {--app-name= : Application name}
                            {--db=mysql : Database driver (mysql, pgsql, sqlite, sqlsrv)}
                            {--db-host=127.0.0.1 : Database host}
                            {--db-port= : Database port}
                            {--db-name= : Database name}
                            {--db-user=root : Database username}
                            {--db-password= : Database password}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Install the Easy Pack package with automatic configuration';

    protected Filesystem $files;

    /**
     * Whether we're running in quick mode.
     */
    protected bool $quickMode = false;

    /**
     * Supported database drivers with default ports.
     */
    protected array $databaseDrivers = [
        'mysql' => ['name' => 'MySQL', 'port' => '3306'],
        'pgsql' => ['name' => 'PostgreSQL', 'port' => '5432'],
        'sqlite' => ['name' => 'SQLite', 'port' => null],
        'sqlsrv' => ['name' => 'SQL Server', 'port' => '1433'],
    ];

    public function __construct(Filesystem $files)
    {
        parent::__construct();
        $this->files = $files;
    }

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $this->quickMode = $this->option('quick');

        $this->components->info('🚀 Installing Easy Pack...');
        if ($this->quickMode) {
            $this->line('  <fg=cyan>Running in quick mode with MySQL defaults</>');
        }
        $this->newLine();

        // Pre-flight checks
        if (!$this->runPreflightChecks()) {
            return Command::FAILURE;
        }

        // Step 1: Configure environment
        $this->configureEnvironment();

        // Step 2: Publish config files
        $this->publishConfigs();

        // Step 3: Publish migrations
        $this->publishMigrations();

        // Step 4: Publish models and entities (always publish these)
        $this->publishModelsAndEntities();

        // Step 4b: Configure User model (patch existing model if needed)
        $this->configureUserModel();

        // Step 5: Publish controllers (API, Admin, Auth)
        if (!$this->option('skip-controllers')) {
            $this->publishControllers();
        }

        // Step 6: Run migrations
        if (!$this->option('skip-migrations')) {
            $this->runMigrations();
        }

        // Step 7: Run seeders
        if (!$this->option('skip-seeders')) {
            $this->runSeeders();
        }

        // Step 8: Publish and configure routes
        if (!$this->option('skip-routes')) {
            $this->publishAndConfigureRoutes();
        }

        // Step 9: Configure Sanctum
        $this->configureSanctumConfig();

        // Step 10: Generate app key if not set
        $this->ensureAppKeyGenerated();

        // Step 11: Generate API documentation (if requested)
        if ($this->option('with-docs')) {
            $this->generateApiDocs();
        }

        // Step 12: Clear all caches
        $this->clearAllCaches();

        // Show success and instructions
        $this->showSuccess();

        return Command::SUCCESS;
    }

    /**
     * Run pre-flight checks before installation.
     */
    protected function runPreflightChecks(): bool
    {
        $this->components->info('Pre-flight Checks');

        $allPassed = true;

        // Check PHP version
        $phpVersion = PHP_VERSION;
        $minPhpVersion = '8.2.0';
        if (version_compare($phpVersion, $minPhpVersion, '>=')) {
            $this->line("  <fg=green>✓</> PHP version: {$phpVersion}");
        } else {
            $this->line("  <fg=red>✗</> PHP version: {$phpVersion} (requires >= {$minPhpVersion})");
            $allPassed = false;
        }

        // Check required PHP extensions
        $requiredExtensions = ['pdo', 'mbstring', 'openssl', 'tokenizer', 'xml', 'ctype', 'json'];
        $dbDriver = $this->option('db') ?: 'mysql';

        // Add database-specific extension
        $pdoExtensions = [
            'mysql' => 'pdo_mysql',
            'pgsql' => 'pdo_pgsql',
            'sqlite' => 'pdo_sqlite',
            'sqlsrv' => 'pdo_sqlsrv',
        ];
        if (isset($pdoExtensions[$dbDriver])) {
            $requiredExtensions[] = $pdoExtensions[$dbDriver];
        }

        $missingExtensions = [];
        foreach ($requiredExtensions as $ext) {
            if (!extension_loaded($ext)) {
                $missingExtensions[] = $ext;
            }
        }

        if (empty($missingExtensions)) {
            $this->line("  <fg=green>✓</> Required PHP extensions loaded");
        } else {
            $this->line("  <fg=red>✗</> Missing PHP extensions: " . implode(', ', $missingExtensions));
            $allPassed = false;
        }

        // Check writable directories
        $writablePaths = [
            base_path('storage') => 'storage/',
            base_path('bootstrap/cache') => 'bootstrap/cache/',
            base_path('.env') => '.env',
        ];

        $notWritable = [];
        foreach ($writablePaths as $path => $name) {
            if ($this->files->exists($path) && !$this->files->isWritable($path)) {
                $notWritable[] = $name;
            }
        }

        if (empty($notWritable)) {
            $this->line("  <fg=green>✓</> Required directories are writable");
        } else {
            $this->line("  <fg=yellow>⚠</> Not writable: " . implode(', ', $notWritable));
            // Don't fail, just warn
        }

        // Check Laravel version
        $laravelVersion = app()->version();
        $this->line("  <fg=green>✓</> Laravel version: {$laravelVersion}");

        $this->newLine();

        if (!$allPassed) {
            $this->components->error('Pre-flight checks failed. Please fix the issues above.');
            return false;
        }

        return true;
    }

    /**
     * Configure the User model to extend Easy Pack's User model.
     */
    protected function configureUserModel(): void
    {
        $this->components->info('Step 4b: Configuring User Model');

        $userModelPath = app_path('Models/User.php');

        if (!$this->files->exists($userModelPath)) {
            $this->line("  <fg=yellow>⚠</> User model not found at {$userModelPath}");
            return;
        }

        $content = $this->files->get($userModelPath);

        // Check if already extending Oxygen User
        if (str_contains($content, 'EasyPack\Models\User')) {
            $this->line("  <fg=green>✓</> User model already configured");
            return;
        }

        // Replace the import
        $searchImport = 'use Illuminate\Foundation\Auth\User as Authenticatable;';
        $replaceImport = 'use EasyPack\Models\User as Authenticatable;';

        if (str_contains($content, $searchImport)) {
            $content = str_replace($searchImport, $replaceImport, $content);
            $this->files->put($userModelPath, $content);
            $this->line("  <fg=green>✓</> User model updated to extend Oxygen User");
        } else {
            // Fallback: try to find class definition if import not found exactly as expected
            // This handles cases where imports might be different or aliased differently
            $this->line("  <fg=yellow>⚠</> Could not automatically patch User model. Please update manually:");
            $this->line("    <fg=gray>Change parent class to: EasyPack\Models\User</>");
        }

        $this->newLine();
    }

    /**
     * Configure environment settings.
     */
    protected function configureEnvironment(): void
    {
        $this->components->info('Step 1: Environment Configuration');

        // In quick mode, use defaults with minimal prompts
        if ($this->quickMode) {
            $appName = $this->option('app-name') ?: env('APP_NAME', 'Laravel');
            $dbDriver = 'mysql'; // MySQL is default for quick mode

            // For quick mode, only ask for essential DB info if not provided
            $suggestedDbName = Str::slug($appName, '_');
            $dbName = $this->option('db-name') ?: $this->askWithDefault('Database name', $suggestedDbName);
            $dbUser = $this->option('db-user') ?: 'root';
            $dbPassword = $this->option('db-password') ?? $this->secret('Database password (press Enter for empty)') ?? '';

            $envUpdates = [
                'APP_NAME' => $this->formatEnvValue($appName),
                'DB_CONNECTION' => $dbDriver,
                'DB_HOST' => $this->option('db-host') ?: '127.0.0.1',
                'DB_PORT' => $this->option('db-port') ?: '3306',
                'DB_DATABASE' => $dbName,
                'DB_USERNAME' => $dbUser,
                'DB_PASSWORD' => $this->formatEnvValue($dbPassword),
            ];

            $this->line("  <fg=green>✓</> Quick mode: MySQL ({$dbName}@127.0.0.1:3306)");
        } else {
            // Interactive mode - ask for everything
            $appName = $this->option('app-name') ?: $this->askWithDefault(
                'What is your application name?',
                env('APP_NAME', 'Laravel')
            );

            // Get database driver
            $dbDriver = $this->option('db');
            if (!isset($this->databaseDrivers[$dbDriver])) {
                $dbDriver = $this->choice(
                    'Select database driver',
                    array_combine(
                        array_keys($this->databaseDrivers),
                        array_column($this->databaseDrivers, 'name')
                    ),
                    'mysql'
                );
            }

            $envUpdates = [
                'APP_NAME' => $this->formatEnvValue($appName),
                'DB_CONNECTION' => $dbDriver,
            ];

            if ($dbDriver === 'sqlite') {
                // SQLite setup
                $envUpdates['DB_DATABASE'] = database_path('database.sqlite');
                $this->createSqliteDatabase();
                $this->line("  <fg=green>✓</> Using SQLite database");
            } else {
                // Other databases
                $suggestedDbName = Str::slug($appName, '_');

                $dbHost = $this->option('db-host') ?: $this->askWithDefault('Database host', '127.0.0.1');
                $dbPort = $this->option('db-port') ?: $this->databaseDrivers[$dbDriver]['port'];
                $dbName = $this->option('db-name') ?: $this->askWithDefault('Database name', $suggestedDbName);
                $dbUser = $this->option('db-user') ?: $this->askWithDefault('Database username', 'root');
                $dbPassword = $this->option('db-password') ?? $this->secret('Database password (press Enter for empty)') ?? '';

                $envUpdates['DB_HOST'] = $dbHost;
                $envUpdates['DB_PORT'] = $dbPort;
                $envUpdates['DB_DATABASE'] = $dbName;
                $envUpdates['DB_USERNAME'] = $dbUser;
                $envUpdates['DB_PASSWORD'] = $this->formatEnvValue($dbPassword);

                $this->line("  <fg=green>✓</> Database: {$this->databaseDrivers[$dbDriver]['name']} ({$dbName}@{$dbHost}:{$dbPort})");
            }
        }

        // Add API Key configuration
        $envUpdates['API_ACTIVE'] = 'false';
        $envUpdates['API_KEY'] = '"sB5ROi64SAEhZz3q0N2aTzgDtDDrB2ZF5b667Nr8efQ="';

        // Update .env file
        $this->updateEnvFile($envUpdates);

        // Clear config cache
        Artisan::call('config:clear', [], $this->output);

        $this->line("  <fg=green>✓</> Environment configured");
        $this->line("  <fg=green>✓</> API key configuration added");
        $this->newLine();
    }

    /**
     * Ask with a default value (simpler than Laravel's ask).
     */
    protected function askWithDefault(string $question, string $default): string
    {
        $answer = $this->ask($question, $default);
        return $answer ?: $default;
    }

    /**
     * Format a value for .env file.
     */
    protected function formatEnvValue(string $value): string
    {
        if ($value === '' || preg_match('/\s|[#"\'\\\\]/', $value)) {
            return '"' . addslashes($value) . '"';
        }
        return $value;
    }

    /**
     * Update the .env file.
     */
    protected function updateEnvFile(array $values): void
    {
        $envPath = base_path('.env');

        if (!$this->files->exists($envPath)) {
            if ($this->files->exists(base_path('.env.example'))) {
                $this->files->copy(base_path('.env.example'), $envPath);
            } else {
                $this->components->error('.env file not found!');
                return;
            }
        }

        $envContent = $this->files->get($envPath);

        foreach ($values as $key => $value) {
            if (preg_match("/^{$key}=.*/m", $envContent)) {
                $envContent = preg_replace("/^{$key}=.*/m", "{$key}={$value}", $envContent);
            } else {
                $envContent .= "\n{$key}={$value}";
            }
        }

        $this->files->put($envPath, $envContent);
    }

    /**
     * Create SQLite database file.
     */
    protected function createSqliteDatabase(): void
    {
        $dbPath = database_path('database.sqlite');
        if (!$this->files->exists($dbPath)) {
            $this->files->put($dbPath, '');
        }
    }

    /**
     * Publish configuration files.
     */
    protected function publishConfigs(): void
    {
        $this->components->info('Step 2: Publishing Configuration');

        // Publish Oxygen config
        Artisan::call('vendor:publish', [
            '--tag' => 'easypack-config',
            '--force' => $this->option('force'),
        ]);

        // Publish Spatie Permission config
        Artisan::call('vendor:publish', [
            '--provider' => 'Spatie\\Permission\\PermissionServiceProvider',
            '--force' => $this->option('force'),
        ]);

        // Publish docs assets (swagger.html, apidoc.json)
        Artisan::call('vendor:publish', [
            '--tag' => 'easypack-docs',
            '--force' => $this->option('force'),
        ]);

        // Configure auth.php to add sanctum guard
        $this->configureSanctumGuard();

        $this->line("  <fg=green>✓</> Config files published");
        $this->line("  <fg=green>✓</> API documentation assets published");
        $this->newLine();
    }

    /**
     * Configure the sanctum guard in auth.php.
     */
    protected function configureSanctumGuard(): void
    {
        $authConfigPath = config_path('auth.php');

        if (!$this->files->exists($authConfigPath)) {
            $this->line("  <fg=yellow>⚠</> auth.php not found, skipping sanctum guard configuration");
            return;
        }

        $content = $this->files->get($authConfigPath);

        // Check if sanctum guard already exists
        if (str_contains($content, "'sanctum'")) {
            $this->line("  <fg=green>✓</> Sanctum guard already configured");
            return;
        }

        // Find the guards array and add sanctum guard after web
        $pattern = "/'web' => \[\s*'driver' => 'session',\s*'provider' => 'users',\s*\],/s";

        if (preg_match($pattern, $content)) {
            $replacement = "'web' => [
            'driver' => 'session',
            'provider' => 'users',
        ],

        'sanctum' => [
            'driver' => 'sanctum',
            'provider' => 'users',
        ],";

            $content = preg_replace($pattern, $replacement, $content);
            $this->files->put($authConfigPath, $content);
            $this->line("  <fg=green>✓</> Sanctum guard added to auth.php");
        } else {
            $this->line("  <fg=yellow>⚠</> Could not auto-configure sanctum guard. Please add manually to config/auth.php");
        }
    }

    /**
     * Publish migration files.
     */
    protected function publishMigrations(): void
    {
        $this->components->info('Step 3: Publishing Migrations');

        Artisan::call('vendor:publish', [
            '--tag' => 'easypack-migrations',
            '--force' => $this->option('force'),
        ]);

        $this->line("  <fg=green>✓</> Migration files published");
        $this->newLine();
    }

    /**
     * Publish models and entities.
     */
    protected function publishModelsAndEntities(): void
    {
        $this->components->info('Step 4: Publishing Models & Entities');

        // Publish models
        Artisan::call('vendor:publish', [
            '--tag' => 'easypack-models',
            '--force' => $this->option('force'),
        ]);
        $this->line("  <fg=green>✓</> Models published (8 files, including PageContent)");

        // Publish entities
        Artisan::call('vendor:publish', [
            '--tag' => 'easypack-entities',
            '--force' => $this->option('force'),
        ]);
        $this->line("  <fg=green>✓</> Entities published (6 files)");

        // Publish page management views
        Artisan::call('vendor:publish', [
            '--tag' => 'easypack-page-views',
            '--force' => $this->option('force'),
        ]);
        $this->line("  <fg=green>✓</> Page management views published");

        // Warning about customizations if force is used
        if ($this->option('force') && $this->files->exists(resource_path('views/vendor/easypack/manage/pages/index.blade.php'))) {
            $this->newLine();
            $this->line("  <fg=yellow>⚠️  Warning: Existing views were overwritten (--force used)</>");
            $this->line("     <fg=gray>If you had customizations, restore them from version control</>");
        }

        $this->newLine();
    }

    /**
     * Run database migrations.
     */
    protected function runMigrations(): void
    {
        $this->components->info('Step 6: Running Migrations');

        // First, test database connection before attempting migrations
        $connectionTest = $this->testDatabaseConnection();

        if ($connectionTest !== true) {
            $this->line("  <fg=red>✗</> Database connection failed");
            $this->line("    <fg=yellow>{$connectionTest}</>");
            $this->line("    <fg=gray>Run 'php artisan migrate' manually after fixing the connection.</>");
            $this->newLine();
            return;
        }

        // Clear all caches to ensure fresh .env values are used
        exec('cd ' . base_path() . ' && php artisan config:clear 2>&1');

        // Run migrate in a fresh process to pick up new .env values
        $output = [];
        $returnCode = 0;
        exec('cd ' . base_path() . ' && php artisan migrate --force 2>&1', $output, $returnCode);

        if ($returnCode === 0) {
            $this->line("  <fg=green>✓</> Migrations completed");
        } else {
            $outputStr = implode(' ', $output);

            // Check for common issues (case-insensitive)
            $outputLower = strtolower($outputStr);

            // Debug: uncomment to see raw output
            // $this->line("DEBUG: " . substr($outputStr, 0, 300));

            if (str_contains($outputLower, 'already exists') || str_contains($outputLower, '42s01') || str_contains($outputLower, '1050')) {
                $this->line("  <fg=yellow>⚠</> Some tables already exist");
                $this->line("    <fg=yellow>Run 'php artisan migrate:fresh' for a clean install</>");
                $this->line("    <fg=yellow>Or 'php artisan migrate' to run pending migrations only</>");
            } elseif (str_contains($outputLower, 'could not find driver') || str_contains($outputLower, 'driver not found') || str_contains($outputLower, 'pdo')) {
                $this->line("  <fg=red>✗</> Database driver issue");
                $this->line("    <fg=yellow>Check PHP PDO extension is installed (php-mysql)</>");
            } elseif (str_contains($outputLower, 'access denied') || str_contains($outputLower, '1045')) {
                $this->line("  <fg=red>✗</> Database access denied");
                $this->line("    <fg=yellow>Check your database username and password in .env</>");
            } elseif (str_contains($outputLower, 'unknown database') || str_contains($outputLower, '1049')) {
                $this->line("  <fg=red>✗</> Database does not exist");
                $this->line("    <fg=yellow>Create the database first, then run 'php artisan migrate'</>");
            } elseif (str_contains($outputLower, 'connection refused') || str_contains($outputLower, '2002')) {
                $this->line("  <fg=red>✗</> Database connection refused");
                $this->line("    <fg=yellow>Is the database server running?</>");
            } else {
                $this->line("  <fg=red>✗</> Migration failed");
                // Show first meaningful error line
                foreach ($output as $line) {
                    $lineLower = strtolower($line);
                    if (str_contains($lineLower, 'sqlstate') || str_contains($lineLower, 'error') || str_contains($lineLower, 'exception')) {
                        $this->line("    <fg=yellow>" . trim(substr($line, 0, 150)) . "</>");
                        break;
                    }
                }
            }
            $this->line("    <fg=gray>Run 'php artisan migrate' manually to see full error.</>");
        }

        $this->newLine();
    }

    /**
     * Test database connection using the .env file directly.
     * Will attempt to create the database if it doesn't exist.
     */
    protected function testDatabaseConnection(): bool|string
    {
        $envPath = base_path('.env');
        if (!$this->files->exists($envPath)) {
            return 'No .env file found';
        }

        // Parse .env file directly to get fresh values
        $envContent = $this->files->get($envPath);
        $envVars = [];

        foreach (explode("\n", $envContent) as $line) {
            $line = trim($line);
            if (empty($line) || str_starts_with($line, '#')) {
                continue;
            }
            if (str_contains($line, '=')) {
                [$key, $value] = explode('=', $line, 2);
                $value = trim($value, '"\'');
                $envVars[trim($key)] = $value;
            }
        }

        $driver = $envVars['DB_CONNECTION'] ?? 'mysql';

        // Skip connection test for SQLite
        if ($driver === 'sqlite') {
            $dbPath = $envVars['DB_DATABASE'] ?? database_path('database.sqlite');
            if (!$this->files->exists($dbPath)) {
                $this->files->put($dbPath, '');
            }
            return true;
        }

        $host = $envVars['DB_HOST'] ?? '127.0.0.1';
        $port = $envVars['DB_PORT'] ?? '3306';
        $database = $envVars['DB_DATABASE'] ?? '';
        $username = $envVars['DB_USERNAME'] ?? 'root';
        $password = $envVars['DB_PASSWORD'] ?? '';

        try {
            $dsn = match ($driver) {
                'mysql' => "mysql:host={$host};port={$port};dbname={$database}",
                'pgsql' => "pgsql:host={$host};port={$port};dbname={$database}",
                'sqlsrv' => "sqlsrv:Server={$host},{$port};Database={$database}",
                default => "mysql:host={$host};port={$port};dbname={$database}",
            };

            $pdo = new \PDO($dsn, $username, $password, [
                \PDO::ATTR_TIMEOUT => 5,
                \PDO::ATTR_ERRMODE => \PDO::ERRMODE_EXCEPTION,
            ]);

            return true;
        } catch (\PDOException $e) {
            $message = $e->getMessage();

            // If database doesn't exist, try to create it
            if (str_contains($message, 'Unknown database') || str_contains($message, '1049')) {
                $created = $this->tryCreateDatabase($driver, $host, $port, $database, $username, $password);
                if ($created === true) {
                    $this->line("  <fg=green>✓</> Database '{$database}' created automatically");
                    return true;
                }
                return $created; // Return error message if creation failed
            }

            if (str_contains($message, 'Access denied')) {
                return "Access denied for user '{$username}'@'{$host}'. Check your password.";
            } elseif (str_contains($message, 'Connection refused')) {
                return "Connection refused. Is the database server running on {$host}:{$port}?";
            } elseif (str_contains($message, 'could not find driver')) {
                return "PHP {$driver} driver not installed. Install php-{$driver} extension.";
            }

            return $message;
        }
    }

    /**
     * Try to create the database if it doesn't exist.
     */
    protected function tryCreateDatabase(string $driver, string $host, string $port, string $database, string $username, string $password): bool|string
    {
        try {
            // Connect without specifying database
            $dsn = match ($driver) {
                'mysql' => "mysql:host={$host};port={$port}",
                'pgsql' => "pgsql:host={$host};port={$port}",
                'sqlsrv' => "sqlsrv:Server={$host},{$port}",
                default => "mysql:host={$host};port={$port}",
            };

            $pdo = new \PDO($dsn, $username, $password, [
                \PDO::ATTR_TIMEOUT => 5,
                \PDO::ATTR_ERRMODE => \PDO::ERRMODE_EXCEPTION,
            ]);

            // Create database with appropriate syntax
            $sql = match ($driver) {
                'mysql' => "CREATE DATABASE IF NOT EXISTS `{$database}` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci",
                'pgsql' => "CREATE DATABASE \"{$database}\"",
                default => "CREATE DATABASE `{$database}`",
            };

            $pdo->exec($sql);
            return true;

        } catch (\PDOException $e) {
            $message = $e->getMessage();

            if (str_contains($message, 'Access denied')) {
                return "Cannot create database: Access denied. Create '{$database}' manually.";
            } elseif (str_contains($message, 'already exists')) {
                // Database exists now, that's fine
                return true;
            }

            return "Cannot create database '{$database}': " . $message;
        }
    }

    /**
     * Run database seeders.
     */
    protected function runSeeders(): void
    {
        $this->components->info('Step 7: Seeding Database');

        // First, publish the DatabaseSeeder and UsersSeeder stubs to replace Laravel defaults
        $this->publishSeederStubs();

        // Test database connection first
        $connectionTest = $this->testDatabaseConnection();

        if ($connectionTest !== true) {
            $this->line("  <fg=yellow>⚠</> Seeding skipped - database connection issue");
            $this->line("    <fg=yellow>Run 'php artisan db:seed' manually after fixing the connection.</>");
            $this->newLine();
            return;
        }

        // Clear config cache to ensure fresh .env values
        exec('cd ' . base_path() . ' && php artisan config:clear 2>&1');

        // Run seeder in a fresh process to pick up new .env values
        $output = [];
        $returnCode = 0;
        exec('cd ' . base_path() . ' && php artisan db:seed --class="Oxygen\\Starter\\Database\\Seeders\\EasyPackSeeder" --force 2>&1', $output, $returnCode);

        if ($returnCode === 0) {
            $this->line("  <fg=green>✓</> Database seeded (roles, permissions, settings)");
            $this->line("    <fg=gray>Users: test@example.com, admin@example.com, superadmin@example.com</>");
            $this->line("    <fg=gray>Password for all users: password</>");
        } else {
            $this->line("  <fg=yellow>⚠</> Seeding skipped");
            $this->line("    <fg=yellow>Run 'php artisan db:seed' manually.</>");
        }

        $this->newLine();
    }

    /**
     * Publish DatabaseSeeder and UsersSeeder stubs to replace Laravel defaults.
     * This ensures 'php artisan migrate:fresh --seed' creates all admin users.
     */
    protected function publishSeederStubs(): void
    {
        // Use vendor:publish to properly publish seeders
        Artisan::call('vendor:publish', [
            '--tag' => 'easypack-seeders',
            '--force' => true,
        ]);

        $this->line("  <fg=green>✓</> DatabaseSeeder.php updated (calls EasyPackSeeder)");
        $this->line("  <fg=green>✓</> UsersSeeder.php published (admin users)");
    }

    /**
     * Publish controllers (API, Admin, Auth).
     */
    protected function publishControllers(): void
    {
        $this->components->info('Step 5: Publishing Controllers');

        // Always publish API controllers (core functionality)
        Artisan::call('vendor:publish', [
            '--tag' => 'easypack-api-controllers',
            '--force' => $this->option('force'),
        ]);
        $this->line("  <fg=green>✓</> API Controllers published (5 files)");

        // Publish Admin controllers (unless skipped)
        if (!$this->option('skip-admin-controllers')) {
            Artisan::call('vendor:publish', [
                '--tag' => 'easypack-admin-controllers',
                '--force' => $this->option('force'),
            ]);
            $this->line("  <fg=green>✓</> Admin Controllers published (4 files)");
        } else {
            $this->line("  <fg=yellow>⊘</> Admin Controllers skipped");
        }

        // Publish Auth controllers (unless skipped)
        if (!$this->option('skip-auth-controllers')) {
            Artisan::call('vendor:publish', [
                '--tag' => 'easypack-auth-controllers',
                '--force' => $this->option('force'),
            ]);
            $this->line("  <fg=green>✓</> Auth Controllers published (2 files)");
        } else {
            $this->line("  <fg=yellow>⊘</> Auth Controllers skipped");
        }

        // Enable local controllers in .env
        $this->enableLocalControllersInEnv();

        $this->newLine();
    }

    /**
     * Enable local controllers in .env file.
     */
    protected function enableLocalControllersInEnv(): void
    {
        $envPath = base_path('.env');

        if (!$this->files->exists($envPath)) {
            $this->line("  <fg=yellow>⚠</> .env file not found. Add manually: EASYPACK_USE_LOCAL_API_CONTROLLERS=true");
            return;
        }

        $envContent = $this->files->get($envPath);
        $additions = [];

        // Always add API controllers setting
        if (!Str::contains($envContent, 'EASYPACK_USE_LOCAL_API_CONTROLLERS')) {
            $additions[] = 'EASYPACK_USE_LOCAL_API_CONTROLLERS=true';
        }

        // Add Admin controllers setting if not skipped
        if (!$this->option('skip-admin-controllers') && !Str::contains($envContent, 'EASYPACK_USE_LOCAL_ADMIN_CONTROLLERS')) {
            $additions[] = 'EASYPACK_USE_LOCAL_ADMIN_CONTROLLERS=true';
        }

        if (!empty($additions)) {
            $envContent .= "\n# Easy Pack Local Controllers\n" . implode("\n", $additions) . "\n";
            $this->files->put($envPath, $envContent);
            $this->line("  <fg=green>✓</> Local controllers enabled in .env");
        }
    }

    /**
     * Show success message and next steps.
     */
    protected function showSuccess(): void
    {
        $this->components->info('✅ Easy Pack installed successfully!');
        $this->newLine();

        // Show what was installed
        $this->components->twoColumnDetail('<fg=cyan>Published Files</>', '<fg=cyan>Location</>');
        $this->components->twoColumnDetail('8 Models (inc. PageContent)', 'app/Models/');
        $this->components->twoColumnDetail('6 Entities', 'app/Entities/');

        if (!$this->option('skip-controllers')) {
            $this->components->twoColumnDetail('5 API Controllers', 'app/Http/Controllers/Api/V1/');
            if (!$this->option('skip-admin-controllers')) {
                $this->components->twoColumnDetail('4 Admin Controllers', 'app/Http/Controllers/Admin/');
            }
            if (!$this->option('skip-auth-controllers')) {
                $this->components->twoColumnDetail('2 Auth Controllers', 'app/Http/Controllers/Auth/');
            }
        }

        $this->components->twoColumnDetail('4 Config files', 'config/');
        $this->components->twoColumnDetail('8 Migrations (inc. pages)', 'database/migrations/');
        $this->components->twoColumnDetail('3 Seeders (inc. pages)', 'database/seeders/');
        $this->components->twoColumnDetail('Page Management Views', 'resources/views/vendor/easypack/');
        $this->components->twoColumnDetail('Swagger UI', 'public/docs/swagger.html');

        if (!$this->option('skip-routes')) {
            $this->components->twoColumnDetail('API Routes', 'routes/api.php');
            $this->components->twoColumnDetail('Page Routes', 'routes/pages.php');
        }

        $this->newLine();
        $this->components->info('👤 Default Users (password: password)');
        $this->line('  <fg=gray>test@example.com      - admin role</>');
        $this->line('  <fg=gray>admin@example.com     - admin role</>');
        $this->line('  <fg=gray>superadmin@example.com - super-admin role</>');

        $this->newLine();
        $this->components->info('🎨 View Customization');
        $this->line('  <fg=green>✓</> Page views published to resources/views/vendor/easypack/');
        $this->line('  <fg=yellow>📌 Publish ALL management views for full customization:</>');
        $this->line('     <fg=cyan>php artisan vendor:publish --tag=easypack-manage-views</>');
        $this->line('  <fg=gray>This includes: users, roles, permissions, layouts, and more</>');
        $this->line('  <fg=gray>See VIEW_CUSTOMIZATION.md for detailed guide</>');

        $this->newLine();
        $this->components->info('📋 Controller Customization');
        $this->line('  <fg=gray>Controllers are now in app/Http/Controllers/ - edit them freely!</>');
        $this->line('  <fg=gray>php artisan easypack:publish --customizable  # Republish all customizable files</>');

        $this->newLine();
        $this->components->info('📄 Page Management System');
        $this->line('  <fg=green>✓</> Admin panel with page editor enabled');
        $this->line('  <fg=gray>Login at /login and go to "Pages" in sidebar</>');
        $this->line('  <fg=gray>Create/Edit pages: /manage/pages</>');
        $this->line('  <fg=gray>Public pages: /privacy-policy, /terms-conditions</>');

        $this->newLine();
        $this->components->info('📖 API Documentation');
        if ($this->option('with-docs')) {
            $this->line('  <fg=green>✓</> API documentation generated');
        } else {
            $this->line('  <fg=gray>php artisan generate:docs  # Generate Swagger/OpenAPI documentation</>');
        }
        $this->line('  <fg=gray>Visit /docs/swagger.html to view interactive API docs</>');

        $this->newLine();
        $this->components->info('🔧 Available Commands');
        $this->line('  <fg=gray>php artisan easypack:publish    # Publish customizable files</>');
        $this->line('  <fg=gray>php artisan generate:docs     # Generate API documentation</>');
        $this->line('  <fg=gray>php artisan make:easypack:crud  # Generate CRUD scaffold</>');

        $this->newLine();
        $this->components->info('🚀 Start the server');
        $this->line('  <fg=gray>php artisan serve</>');
        $this->line('  <fg=gray>API: http://localhost:8000/api/v1/guests</>');
        $this->line('  <fg=gray>Admin: http://localhost:8000/login (admin@example.com / password)</>');
        $this->line('  <fg=gray>Pages: http://localhost:8000/manage/pages</>');
    }

    /**
     * Publish and configure routes for Laravel 11+.
     */
    protected function publishAndConfigureRoutes(): void
    {
        $this->components->info('Step 8: Publishing & Configuring Routes');

        // Publish API routes
        Artisan::call('vendor:publish', [
            '--tag' => 'easypack-routes',
            '--force' => $this->option('force'),
        ]);
        $this->line("  <fg=green>✓</> API routes published to routes/api.php");

        // Publish page management routes
        Artisan::call('vendor:publish', [
            '--tag' => 'easypack-page-routes',
            '--force' => $this->option('force'),
        ]);
        $this->line("  <fg=green>✓</> Page management routes published to routes/pages.php");

        // Add pages.php to web.php if not already included
        $this->includePagesRoutes();

        // Configure Laravel 11+ bootstrap/app.php
        $this->configureBootstrapApp();

        $this->newLine();
    }

    /**
     * Include pages.php routes in web.php
     */
    protected function includePagesRoutes(): void
    {
        $webRoutesPath = base_path('routes/web.php');

        if (!$this->files->exists($webRoutesPath)) {
            $this->line("  <fg=yellow>⚠</> routes/web.php not found");
            return;
        }

        $content = $this->files->get($webRoutesPath);

        // Check if pages.php is already included
        if (str_contains($content, "require __DIR__.'/pages.php'") ||
            str_contains($content, 'pages.php')) {
            $this->line("  <fg=green>✓</> Page routes already included in web.php");
            return;
        }

        // Add include at the end of the file
        $includeStatement = "\n// Page Management Routes\nrequire __DIR__.'/pages.php';\n";
        $newContent = rtrim($content) . $includeStatement;

        $this->files->put($webRoutesPath, $newContent);
        $this->line("  <fg=green>✓</> Page routes included in routes/web.php");
    }

    /**
     * Configure bootstrap/app.php for Laravel 11+ to include API routes.
     */
    protected function configureBootstrapApp(): void
    {
        $bootstrapPath = base_path('bootstrap/app.php');

        if (!$this->files->exists($bootstrapPath)) {
            $this->line("  <fg=yellow>⚠</> bootstrap/app.php not found (Laravel 10 or earlier?)");
            return;
        }

        $content = $this->files->get($bootstrapPath);

        // Check if this is Laravel 11+ style (has Application::configure)
        if (!str_contains($content, 'Application::configure')) {
            $this->line("  <fg=gray>-</> Laravel 10 detected, skipping bootstrap/app.php configuration");
            return;
        }

        // Check if API routes are already configured
        if (str_contains($content, "api: __DIR__.'/../routes/api.php'") ||
            str_contains($content, 'api:') && str_contains($content, 'routes/api.php')) {
            $this->line("  <fg=green>✓</> API routes already configured in bootstrap/app.php");
            return;
        }

        // Find withRouting and add api route
        // Pattern: ->withRouting(\n    web: ... ,
        $pattern = '/->withRouting\(\s*\n(\s*)web:\s*__DIR__\s*\.\s*\'\/\.\.\/routes\/web\.php\'/';

        if (preg_match($pattern, $content, $matches)) {
            $indent = $matches[1];
            $replacement = "->withRouting(\n{$indent}web: __DIR__.'/../routes/web.php',\n{$indent}api: __DIR__.'/../routes/api.php'";

            $newContent = preg_replace($pattern, $replacement, $content);

            if ($newContent !== $content) {
                $this->files->put($bootstrapPath, $newContent);
                $this->line("  <fg=green>✓</> API routes configured in bootstrap/app.php");
                return;
            }
        }

        // Alternative pattern for different formatting
        $altPattern = '/->withRouting\(\s*web:\s*__DIR__\s*\.\s*\'\/\.\.\/routes\/web\.php\'/';

        if (preg_match($altPattern, $content)) {
            $replacement = "->withRouting(\n        web: __DIR__.'/../routes/web.php',\n        api: __DIR__.'/../routes/api.php'";

            $newContent = preg_replace($altPattern, $replacement, $content);

            if ($newContent !== $content) {
                $this->files->put($bootstrapPath, $newContent);
                $this->line("  <fg=green>✓</> API routes configured in bootstrap/app.php");
                return;
            }
        }

        // If we couldn't auto-configure, show manual instructions
        $this->line("  <fg=yellow>⚠</> Could not auto-configure bootstrap/app.php");
        $this->line("    <fg=gray>Add this to withRouting() in bootstrap/app.php:</>");
        $this->line("    <fg=gray>api: __DIR__.'/../routes/api.php',</>");
    }

    /**
     * Configure Sanctum config to use custom PersonalAccessToken model.
     */
    protected function configureSanctumConfig(): void
    {
        $this->components->info('Step 9: Configuring Sanctum');

        $sanctumConfigPath = config_path('sanctum.php');

        // If sanctum config doesn't exist, publish it first
        if (!$this->files->exists($sanctumConfigPath)) {
            Artisan::call('vendor:publish', [
                '--provider' => 'Laravel\\Sanctum\\SanctumServiceProvider',
                '--tag' => 'sanctum-config',
            ]);
            $this->line("  <fg=green>✓</> Sanctum config published");
        }

        if (!$this->files->exists($sanctumConfigPath)) {
            $this->line("  <fg=yellow>⚠</> Sanctum config not found, skipping");
            $this->newLine();
            return;
        }

        $content = $this->files->get($sanctumConfigPath);

        // Check if already configured with our custom model
        if (str_contains($content, 'App\\Models\\PersonalAccessToken')) {
            $this->line("  <fg=green>✓</> Sanctum already configured with custom PersonalAccessToken");
            $this->newLine();
            return;
        }

        // Try multiple replacement patterns
        $modified = false;

        // Pattern 1: Replace existing personal_access_token line
        if (preg_match("/'personal_access_token'\s*=>/", $content)) {
            $content = preg_replace(
                "/'personal_access_token'\s*=>\s*[^,]+,/",
                "'personal_access_token' => App\\Models\\PersonalAccessToken::class,",
                $content
            );
            $modified = true;
        }
        // Pattern 2: Config doesn't have personal_access_token key - add it after 'return ['
        elseif (!str_contains($content, 'personal_access_token')) {
            $personalAccessTokenConfig = <<<'PHP'

    /*
    |--------------------------------------------------------------------------
    | Personal Access Token Model
    |--------------------------------------------------------------------------
    |
    | This option allows you to specify which model should be used for the
    | personal access tokens. The model must be an extension of the
    | PersonalAccessToken model provided by Laravel Sanctum.
    |
    */

    'personal_access_token' => App\Models\PersonalAccessToken::class,

PHP;
            // Insert after 'return ['
            $content = preg_replace(
                '/return\s*\[\s*\n/',
                "return [\n" . $personalAccessTokenConfig,
                $content,
                1
            );
            $modified = true;
        }

        if ($modified) {
            $this->files->put($sanctumConfigPath, $content);
            $this->line("  <fg=green>✓</> Sanctum configured to use App\\Models\\PersonalAccessToken");
        } else {
            $this->line("  <fg=yellow>⚠</> Could not auto-configure Sanctum. Please update config/sanctum.php:");
            $this->line("    <fg=gray>'personal_access_token' => App\\Models\\PersonalAccessToken::class,</>");
        }

        $this->newLine();
    }

    /**
     * Ensure application key is generated.
     */
    protected function ensureAppKeyGenerated(): void
    {
        $this->components->info('Step 10: Application Key');

        $envPath = base_path('.env');
        if (!$this->files->exists($envPath)) {
            $this->line("  <fg=yellow>⚠</> .env file not found");
            $this->newLine();
            return;
        }

        $envContent = $this->files->get($envPath);

        // Check if APP_KEY is set
        if (preg_match('/^APP_KEY=.+$/m', $envContent)) {
            $key = env('APP_KEY');
            if ($key && strlen($key) > 10) {
                $this->line("  <fg=green>✓</> Application key already set");
                $this->newLine();
                return;
            }
        }

        // Generate new key
        Artisan::call('key:generate', ['--force' => true]);
        $this->line("  <fg=green>✓</> Application key generated");

        $this->newLine();
    }

    /**
     * Generate API documentation.
     */
    protected function generateApiDocs(): void
    {
        $this->components->info('Step 11: Generating API Documentation');

        // Run generate:docs command
        $output = [];
        $returnCode = 0;
        exec('cd ' . base_path() . ' && php artisan generate:docs 2>&1', $output, $returnCode);

        if ($returnCode === 0) {
            $this->line("  <fg=green>✓</> API documentation generated");
            $this->line("    <fg=gray>View at: /docs/swagger.html</>");
        } else {
            $this->line("  <fg=yellow>⚠</> Documentation generation had issues");
            $this->line("    <fg=gray>Run 'php artisan generate:docs' manually to see details.</>");
        }

        $this->newLine();
    }

    /**
     * Clear all application caches.
     */
    protected function clearAllCaches(): void
    {
        $this->components->info('Step 12: Clearing Caches');

        exec('cd ' . base_path() . ' && php artisan config:clear 2>&1');
        exec('cd ' . base_path() . ' && php artisan route:clear 2>&1');
        exec('cd ' . base_path() . ' && php artisan view:clear 2>&1');

        $this->line("  <fg=green>✓</> All caches cleared");
        $this->newLine();
    }
}
