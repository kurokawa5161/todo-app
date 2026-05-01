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
        Schema::create('export_templates', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')
                ->constrained()
                ->cascadeOnDelete();
            $table->string('name');
            $table->string('description');
            $table->enum('format', ['csv', 'excel', 'json', 'xml']);
            $table->json('fields'); // 選択フィールド ['id', 'title', 'content', ...]
            $table->json('order')->nullable(); // 並び順 ['title', 'category', ...]
            $table->json('filters')->nullable(); // フィルター条件
            $table->timestamps();
            $table->unique(['user_id', 'name']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('export_templates');
    }
};
