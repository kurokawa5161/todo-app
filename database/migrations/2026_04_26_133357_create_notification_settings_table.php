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
        Schema::create('notification_settings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')
                ->constrained()
                ->cascadeOnDelete();
            $table->json('reminder_days')->default('[3]');
            $table->boolean('weekly_report_enabled')->default(true);
            $table->boolean('task_assigned_enabled')->default(true);
            $table->boolean('comment_email_enabled')->default(true);
            $table->string('weekly_report_day')->default('monday'); //曜日
            $table->string('weekly_report_time')->default('09:00'); //時刻
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('notification_settings');
    }
};
