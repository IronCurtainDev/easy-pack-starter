<?php

namespace EasyPack\Console\Commands;

use EasyPack\Models\PushNotification;
use Illuminate\Console\Command;
use Kreait\Firebase\Factory;
use Kreait\Firebase\Messaging\CloudMessage;

class SendPushNotifications extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'easypack:send-notifications
                            {--limit=100 : Maximum number of notifications to process}
                            {--stats : Show notification statistics only}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Process and send pending push notifications';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        // Show stats only
        if ($this->option('stats')) {
            $this->showStats();
            return self::SUCCESS;
        }

        $limit = (int) $this->option('limit');
        $credentialsPath = config('easypack-push-notifications.firebase.credentials_path');

        if (!$credentialsPath || !file_exists($credentialsPath)) {
            $this->warn('⚠️  Firebase is not configured. Notifications will be stored but not sent.');
            $this->info('   To configure Firebase:');
            $this->info('   1. Download your Firebase service account JSON file');
            $this->info('   2. Place it in storage/app/firebase-credentials.json');
            $this->info('   3. Set FIREBASE_CREDENTIALS_PATH in your .env file');
            $this->newLine();
            return self::SUCCESS;
        }

        $this->info("Processing pending notifications (limit: {$limit})...");

        $pending = PushNotification::readyToSend()->limit($limit)->get();

        if ($pending->isEmpty()) {
            $this->info('No pending notifications to process.');
            return self::SUCCESS;
        }

        $this->info("Found {$pending->count()} pending notifications.");
        $this->newLine();

        $bar = $this->output->createProgressBar($pending->count());
        $bar->start();

        $sent = 0;
        $failed = 0;

        $factory = (new Factory)->withServiceAccount($credentialsPath);
        $messaging = $factory->createMessaging();

        foreach ($pending as $notification) {
            try {
                $this->sendNotification($messaging, $notification);
                $notification->markAsSent();
                $sent++;
            } catch (\Exception $e) {
                $notification->markAsFailed();
                $failed++;
                \Log::error('Push notification failed', [
                    'id' => $notification->id,
                    'error' => $e->getMessage(),
                ]);
            }

            $bar->advance();
        }

        $bar->finish();
        $this->newLine(2);

        $this->table(
            ['Metric', 'Count'],
            [
                ['Processed', $pending->count()],
                ['Sent', $sent],
                ['Failed', $failed],
            ]
        );

        if ($failed > 0) {
            $this->warn("⚠️  {$failed} notifications failed to send. Check logs for details.");
        }

        return self::SUCCESS;
    }

    /**
     * Send a single notification via Firebase.
     */
    protected function sendNotification($messaging, PushNotification $notification): void
    {
        $message = CloudMessage::new()
            ->withNotification([
                'title' => $notification->title,
                'body' => $notification->message,
            ])
            ->withData($notification->data ?? []);

        if ($notification->topic) {
            $message = $message->withTopic($notification->topic);
            $messaging->send($message);
        } elseif (!empty($notification->tokens)) {
            foreach (array_chunk($notification->tokens, 500) as $tokenBatch) {
                $messaging->sendMulticast($message, $tokenBatch);
            }
        }
    }

    /**
     * Show notification statistics.
     */
    protected function showStats(): void
    {
        $credentialsPath = config('easypack-push-notifications.firebase.credentials_path');

        $this->table(
            ['Metric', 'Value'],
            [
                ['Pending', PushNotification::pending()->count()],
                ['Sent Today', PushNotification::sent()->whereDate('sent_at', today())->count()],
                ['Total Sent', PushNotification::sent()->count()],
                ['Failed', PushNotification::failed()->count()],
                ['Firebase Configured', ($credentialsPath && file_exists($credentialsPath)) ? 'Yes' : 'No'],
            ]
        );
    }
}
