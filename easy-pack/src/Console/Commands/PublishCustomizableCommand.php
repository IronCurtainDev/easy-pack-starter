<?php

namespace EasyPack\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Support\Str;

class PublishCustomizableCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'easypack:publish
        {--customizable : Publish all customizable files (models, controllers, entities)}
        {--models : Publish all customizable models (User, PersonalAccessToken, Setting, etc.)}
        {--controllers : Publish all customizable controllers (API + Admin + Auth)}
        {--api-controllers : Publish API controllers only}
        {--admin-controllers : Publish Admin controllers only}
        {--auth-controllers : Publish Web Auth controllers only}
        {--entities : Publish repository entities}
        {--force : Overwrite existing files}
        {--enable : Also update .env to enable local controllers}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Publish customizable Oxygen files (models, controllers, entities) to your app';

    /**
     * The filesystem instance.
     *
     * @var Filesystem
     */
    protected Filesystem $files;

    /**
     * Stub mappings for each publish type.
     *
     * @var array
     */
    protected array $publishMappings = [
        'models' => [
            'stubs/models/User.stub' => 'app/Models/User.php',
            'stubs/models/PersonalAccessToken.stub' => 'app/Models/PersonalAccessToken.php',
            'stubs/models/Setting.stub' => 'app/Models/Setting.php',
            'stubs/models/SettingGroup.stub' => 'app/Models/SettingGroup.php',
            'stubs/models/Invitation.stub' => 'app/Models/Invitation.php',
            'stubs/models/PushNotification.stub' => 'app/Models/PushNotification.php',
            'stubs/models/NotificationPreference.stub' => 'app/Models/NotificationPreference.php',
            'stubs/models/PageContent.stub' => 'app/Models/PageContent.php',
        ],
        'api-controllers' => [
            'stubs/controllers/Api/AuthController.stub' => 'app/Http/Controllers/Api/AuthController.php',
            'stubs/controllers/Api/ProfileController.stub' => 'app/Http/Controllers/Api/ProfileController.php',
            'stubs/controllers/Api/GuestController.stub' => 'app/Http/Controllers/Api/GuestController.php',
            'stubs/controllers/Api/ForgotPasswordController.stub' => 'app/Http/Controllers/Api/ForgotPasswordController.php',
            'stubs/controllers/Api/DeviceController.stub' => 'app/Http/Controllers/Api/DeviceController.php',
        ],
        'admin-controllers' => [
            'stubs/controllers/Admin/DashboardController.stub' => 'app/Http/Controllers/Admin/DashboardController.php',
            'stubs/controllers/Admin/ManageUsersController.stub' => 'app/Http/Controllers/Admin/ManageUsersController.php',
            'stubs/controllers/Admin/ManageRolesController.stub' => 'app/Http/Controllers/Admin/ManageRolesController.php',
            'stubs/controllers/Admin/ManagePermissionsController.stub' => 'app/Http/Controllers/Admin/ManagePermissionsController.php',
        ],
        'auth-controllers' => [
            'stubs/controllers/Auth/LoginController.stub' => 'app/Http/Controllers/Auth/LoginController.php',
            'stubs/controllers/Auth/ProfileController.stub' => 'app/Http/Controllers/Auth/ProfileController.php',
        ],
        'entities' => [
            'stubs/entities/BaseRepository.stub' => 'app/Entities/BaseRepository.php',
            'stubs/entities/Users/UsersRepository.stub' => 'app/Entities/Users/UsersRepository.php',
            'stubs/entities/Media/Media.stub' => 'app/Entities/Media/Media.php',
            'stubs/entities/Media/MediaRepository.stub' => 'app/Entities/Media/MediaRepository.php',
            'stubs/entities/Settings/SettingsRepository.stub' => 'app/Entities/Settings/SettingsRepository.php',
            'stubs/entities/Settings/SettingGroupsRepository.stub' => 'app/Entities/Settings/SettingGroupsRepository.php',
        ],
    ];

    /**
     * Create a new command instance.
     *
     * @param Filesystem $files
     */
    public function __construct(Filesystem $files)
    {
        parent::__construct();
        $this->files = $files;
    }

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle(): int
    {
        $publishedCount = 0;
        $skippedCount = 0;
        $force = $this->option('force');

        // Determine what to publish
        $publishTypes = $this->getPublishTypes();

        if (empty($publishTypes)) {
            $this->showUsageHelp();
            return self::SUCCESS;
        }

        $this->components->info('Publishing Oxygen customizable files...');
        $this->newLine();

        foreach ($publishTypes as $type) {
            if (!isset($this->publishMappings[$type])) {
                continue;
            }

            $this->components->task("Publishing {$type}", function () use ($type, $force, &$publishedCount, &$skippedCount) {
                foreach ($this->publishMappings[$type] as $stub => $destination) {
                    $result = $this->publishFile($stub, $destination, $force);
                    if ($result === 'published') {
                        $publishedCount++;
                    } elseif ($result === 'skipped') {
                        $skippedCount++;
                    }
                }
                return true;
            });
        }

        $this->newLine();

        // Summary
        if ($publishedCount > 0) {
            $this->components->info("Published {$publishedCount} file(s).");
        }

        if ($skippedCount > 0) {
            $this->components->warn("Skipped {$skippedCount} existing file(s). Use --force to overwrite.");
        }

        // Show next steps
        $this->showNextSteps($publishTypes);

        // Optionally enable local controllers in .env
        if ($this->option('enable')) {
            $this->enableLocalControllers($publishTypes);
        }

        return self::SUCCESS;
    }

    /**
     * Get the publish types based on options.
     *
     * @return array
     */
    protected function getPublishTypes(): array
    {
        $types = [];

        if ($this->option('customizable')) {
            return ['models', 'api-controllers', 'admin-controllers', 'auth-controllers', 'entities'];
        }

        if ($this->option('models')) {
            $types[] = 'models';
        }

        if ($this->option('controllers')) {
            $types[] = 'api-controllers';
            $types[] = 'admin-controllers';
            $types[] = 'auth-controllers';
        }

        if ($this->option('api-controllers')) {
            $types[] = 'api-controllers';
        }

        if ($this->option('admin-controllers')) {
            $types[] = 'admin-controllers';
        }

        if ($this->option('auth-controllers')) {
            $types[] = 'auth-controllers';
        }

        if ($this->option('entities')) {
            $types[] = 'entities';
        }

        return array_unique($types);
    }

    /**
     * Publish a single file.
     *
     * @param string $stub
     * @param string $destination
     * @param bool $force
     * @return string 'published', 'skipped', or 'error'
     */
    protected function publishFile(string $stub, string $destination, bool $force): string
    {
        $stubPath = dirname(__DIR__, 3) . '/' . $stub;
        $destPath = base_path($destination);

        if (!$this->files->exists($stubPath)) {
            $this->components->error("Stub not found: {$stub}");
            return 'error';
        }

        // Check if destination exists
        if ($this->files->exists($destPath) && !$force) {
            return 'skipped';
        }

        // Create directory if needed
        $directory = dirname($destPath);
        if (!$this->files->isDirectory($directory)) {
            $this->files->makeDirectory($directory, 0755, true);
        }

        // Copy the stub
        $this->files->copy($stubPath, $destPath);

        return 'published';
    }

    /**
     * Show usage help when no options provided.
     */
    protected function showUsageHelp(): void
    {
        $this->components->info('Oxygen Customizable Publisher');
        $this->newLine();
        $this->line('This command publishes customizable files to your app, allowing you to');
        $this->line('override Easy Pack\'s default behavior.');
        $this->newLine();

        $this->components->twoColumnDetail('<fg=yellow>Option</>', '<fg=yellow>Description</>');
        $this->components->twoColumnDetail('--customizable', 'Publish all (models, controllers, entities)');
        $this->components->twoColumnDetail('--models', 'Publish all models (User, PersonalAccessToken, Setting, etc.)');
        $this->components->twoColumnDetail('--controllers', 'Publish all controllers (API + Admin + Auth)');
        $this->components->twoColumnDetail('--api-controllers', 'Publish API controllers only');
        $this->components->twoColumnDetail('--admin-controllers', 'Publish Admin controllers only');
        $this->components->twoColumnDetail('--auth-controllers', 'Publish Web Auth controllers only');
        $this->components->twoColumnDetail('--entities', 'Publish repository entities');
        $this->components->twoColumnDetail('--force', 'Overwrite existing files');
        $this->components->twoColumnDetail('--enable', 'Update .env to enable local controllers');

        $this->newLine();
        $this->components->info('Examples:');
        $this->line('  php artisan oxygen:publish --customizable');
        $this->line('  php artisan oxygen:publish --models --controllers --enable');
        $this->line('  php artisan oxygen:publish --api-controllers --force');
    }

    /**
     * Show next steps after publishing.
     *
     * @param array $publishTypes
     */
    protected function showNextSteps(array $publishTypes): void
    {
        $this->newLine();
        $this->components->info('Next Steps:');

        $steps = [];

        if (in_array('models', $publishTypes)) {
            $steps[] = 'Update <fg=cyan>config/oxygen.php</> to use your User model:';
            $steps[] = "  <fg=gray>'user_model' => \\App\\Models\\User::class</>";
        }

        if (in_array('api-controllers', $publishTypes) || in_array('admin-controllers', $publishTypes)) {
            $steps[] = 'Enable local controllers in <fg=cyan>.env</>:';

            if (in_array('api-controllers', $publishTypes)) {
                $steps[] = '  <fg=gray>EASYPACK_USE_LOCAL_API_CONTROLLERS=true</>';
            }

            if (in_array('admin-controllers', $publishTypes)) {
                $steps[] = '  <fg=gray>EASYPACK_USE_LOCAL_ADMIN_CONTROLLERS=true</>';
            }

            $steps[] = '';
            $steps[] = 'Or configure individual controllers in <fg=cyan>config/oxygen.php</>:';
            $steps[] = "  <fg=gray>'local_api_controllers' => ['auth' => \\App\\Http\\Controllers\\Api\\AuthController::class]</>";
        }

        if (in_array('entities', $publishTypes)) {
            $steps[] = 'Register UsersRepository in <fg=cyan>AppServiceProvider</>:';
            $steps[] = "  <fg=gray>\$this->app->bind(UsersRepository::class);</>";
        }

        foreach ($steps as $step) {
            $this->line("  {$step}");
        }

        $this->newLine();
        $this->line('  Run <fg=cyan>--enable</> flag to auto-update .env file.');
    }

    /**
     * Enable local controllers in .env file.
     *
     * @param array $publishTypes
     */
    protected function enableLocalControllers(array $publishTypes): void
    {
        $envPath = base_path('.env');

        if (!$this->files->exists($envPath)) {
            $this->components->warn('.env file not found. Please add the following manually:');
            return;
        }

        $envContent = $this->files->get($envPath);
        $additions = [];

        if (in_array('api-controllers', $publishTypes)) {
            if (!Str::contains($envContent, 'EASYPACK_USE_LOCAL_API_CONTROLLERS')) {
                $additions[] = 'EASYPACK_USE_LOCAL_API_CONTROLLERS=true';
            }
        }

        if (in_array('admin-controllers', $publishTypes)) {
            if (!Str::contains($envContent, 'EASYPACK_USE_LOCAL_ADMIN_CONTROLLERS')) {
                $additions[] = 'EASYPACK_USE_LOCAL_ADMIN_CONTROLLERS=true';
            }
        }

        if (!empty($additions)) {
            $envContent .= "\n# Oxygen Local Controllers\n" . implode("\n", $additions) . "\n";
            $this->files->put($envPath, $envContent);

            $this->components->info('Updated .env with local controller settings.');
        }
    }
}
