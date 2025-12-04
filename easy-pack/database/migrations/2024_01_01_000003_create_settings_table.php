<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('settings', function (Blueprint $table) {
            $table->id();
            $table->string('setting_key', 100)->index()->unique('settings_key_unique');
            $table->string('setting_data_type')->nullable();
            $table->text('setting_value')->nullable();
            $table->string('description')->nullable();
            $table->boolean('is_key_editable')->default(true);
            $table->boolean('is_value_editable')->default(true);
            $table->foreignId('setting_group_id')->nullable()->constrained('setting_groups')->nullOnDelete();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('settings');
    }
};
