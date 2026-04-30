<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // 不正な日付値を修正（NULL, 空文字列, '0000-00-00' など）
        DB::statement("
            UPDATE todos
            SET start_date = end_date
            WHERE start_date IS NULL
               OR start_date = ''
               OR start_date = '0000-00-00'
               OR start_date < '1900-01-01'
        ");

        // NOT NULL制約を追加
        Schema::table('todos', function (Blueprint $table) {
            $table->date('start_date')->nullable(false)->change();
        });
    }

    public function down(): void
    {
        Schema::table('todos', function (Blueprint $table) {
            $table->date('start_date')->nullable()->change();
        });
    }
};
