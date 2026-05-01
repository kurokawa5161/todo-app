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
        Schema::create('dashboard_widgets', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')
                ->constrained()
                ->cascadeOnDelete();
            $table->enum('widget_type', ['stats', 'chart_weekly', 'chart_monthly', 'chart_yearly', 'heatmap', 'gantt', 'recent_todos', 'category_summary', 'priority_summary']);
            $table->integer('position')->default(0);
            $table->enum('size', ['small', 'medium', 'large', 'full'])->default('medium');
            $table->boolean('is_visible')->default(true);
            $table->json('settings')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('dashboard_widgets');
    }
};
