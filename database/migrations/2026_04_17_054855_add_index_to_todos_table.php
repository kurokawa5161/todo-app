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
            $table->index('completed_at');
            $table->index('end_date');
            $table->index('is_pinned');
            $table->index(['user_id', 'parent_id']);
            $table->index(['is_pinned', 'end_date']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('todos', function (Blueprint $table) {
            $table->dropIndex('completed_at');
            $table->dropIndex('end_date');
            $table->dropIndex('is_pinned');
            $table->dropIndex(['user_id', 'parent_id']);
            $table->dropIndex(['is_pinned', 'end_date']);
        });
    }
};
