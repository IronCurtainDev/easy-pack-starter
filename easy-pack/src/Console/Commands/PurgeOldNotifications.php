<?php

namespace EasyPack\Console\Commands;

use EasyPack\Models\PushNotification;
use Illuminate\Console\Command;

class PurgeOldNotifications extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'easypack:purge-notifications
                            {--days= : Days to keep notifications (default from config)}
                            {--dry-run : Show what would be deleted without actually deleting}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Purge old push notifications';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $days = $this->option('days') ?? config('easypack-push-notifications.settings.retention_days', 90);
        $dryRun = $this->option('dry-run');

        $this->info("Checking for notifications older than {$days} days...");

        $query = PushNotification::where('created_at', '<', now()->subDays($days));

        if ($dryRun) {
            $count = $query->count();
            $this->info("Would delete {$count} notifications (dry run).");
            return self::SUCCESS;
        }

        $deleted = $query->delete();

        $this->info("✅ Deleted {$deleted} old notifications.");

        return self::SUCCESS;
    }
}
