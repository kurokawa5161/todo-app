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
        Schema::table('todos', function (Blueprint $table) {
            $table->index('category_id');
            $table->index('priority');
            $table->index('team_id');
            $table->index('assigned_to');
            $table->index(['user_id', 'completed_at']);
            $table->index(['category_id', 'completed_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('todos', function (Blueprint $table) {
            $table->dropIndex('category_id');
            $table->dropIndex('priority');
            $table->dropIndex('team_id');
            $table->dropIndex('assigned_to');
            $table->dropIndex(['user_id', 'completed_at']);
            $table->dropIndex(['category_id', 'completed_at']);
        });
    }
};
