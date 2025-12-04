<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('push_notifications', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->string('title');
            $table->text('message');
            $table->json('data')->nullable();
            $table->string('topic')->nullable()->index();
            $table->json('tokens')->nullable();
            $table->string('status')->default('pending')->index();
            $table->string('category')->nullable();
            $table->string('priority')->default('normal');
            $table->boolean('is_silent')->default(false);
            $table->nullableMorphs('notifiable'); // Adds notifiable_type and notifiable_id
            $table->dateTime('scheduled_at')->nullable();
            $table->dateTime('sent_at')->nullable();
            $table->timestamps();

            // Index for querying pending notifications
            $table->index(['status', 'scheduled_at']);
        });

        // Create notification status pivot table (for tracking read status per user)
        Schema::create('push_notification_user', function (Blueprint $table) {
            $table->id();
            $table->foreignId('push_notification_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->dateTime('read_at')->nullable();
            $table->timestamps();

            $table->unique(['push_notification_id', 'user_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('push_notification_user');
        Schema::dropIfExists('push_notifications');
    }
};
