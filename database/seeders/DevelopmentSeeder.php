<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use App\Models\Category;
use App\Models\Todo;
use App\Models\Tag;
use App\Models\NotificationSetting;

class DevelopmentSeeder extends Seeder
{
    public function run(): void
    {
        // ユーザー作成
        $user = User::create([
            'name' => 'Test User',
            'email' => 'test@example.com',
            'password' => bcrypt('password'),
        ]);

        // カテゴリ作成
        $categories = [
            ['name' => '仕事', 'color' => '#3B82F6'],
            ['name' => '個人', 'color' => '#10B981'],
            ['name' => '買い物', 'color' => '#F59E0B'],
        ];

        foreach ($categories as $cat) {
            Category::create([
                'user_id' => $user->id,
                'name' => $cat['name'],
                'color' => $cat['color'],
            ]);
        }

        // Todo作成
        $todos = [
            ['title' => 'プロジェクト資料作成', 'priority' => 'high', 'days' => 1],
            ['title' => '会議準備', 'priority' => 'medium', 'days' => 3],
            ['title' => 'レポート提出', 'priority' => 'low', 'days' => 7],
        ];

        foreach ($todos as $t) {
            Todo::create([
                'user_id' => $user->id,
                'category_id' => Category::first()->id,
                'title' => $t['title'],
                'content' => 'これはテストデータです',
                'end_date' => now()->addDays($t['days']),
                'priority' => $t['priority'],
            ]);
        }

        // タグ作成
        $tags = ['重要', '緊急', '後回し'];
        foreach ($tags as $tagName) {
            Tag::create([
                'name' => $tagName,
                'user_id' => $user->id,
            ]);
        }

        // 通知設定作成
        NotificationSetting::create([
            'user_id' => $user->id,
            'reminder_days' => [1, 3, 7],
            'weekly_report_enabled' => true,
            'task_assigned_enabled' => true,
            'comment_email_enabled' => true,
        ]);

        $this->command->info('開発用データ作成完了！');
    }
}
