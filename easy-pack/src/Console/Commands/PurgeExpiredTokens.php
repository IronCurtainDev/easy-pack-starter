<?php

namespace EasyPack\Console\Commands;

use EasyPack\Models\PersonalAccessToken;
use Illuminate\Console\Command;

class PurgeExpiredTokens extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'easypack:purge-tokens
                            {--dry-run : Show how many tokens would be deleted without actually deleting them}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Purge all expired personal access tokens from the database';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $expiredCount = PersonalAccessToken::expired()->count();

        if ($expiredCount === 0) {
            $this->info('No expired tokens found.');
            return Command::SUCCESS;
        }

        if ($this->option('dry-run')) {
            $this->info("Found {$expiredCount} expired token(s) that would be deleted.");
            return Command::SUCCESS;
        }

        $deleted = PersonalAccessToken::purgeExpired();

        $this->info("Successfully purged {$deleted} expired token(s).");

        return Command::SUCCESS;
    }
}
