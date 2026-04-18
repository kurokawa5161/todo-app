<?php

namespace Database\Factories;

use App\Models\Comment;
use App\Models\Todo;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Comment>
 */
class CommentFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $comments = [
            '了解しました。対応します。',
            '確認しました。問題ありません。',
            'もう少し詳細を教えてください。',
            '進捗状況を報告します：50%完了しています。',
            '明日までに完了予定です。',
            'この件について、ミーティングで相談したいです。',
            '参考資料を共有しました。ご確認ください。',
            '一旦保留にして、優先度の高いタスクを先に対応します。',
            '完了しました。レビューをお願いします。',
            '問題が発生しました。サポートが必要です。',
            '良いアイデアですね。検討してみます。',
            '次回のミーティングで話し合いましょう。',
            'ドキュメントを更新しておきます。',
            '他のメンバーにも共有しました。',
            '期限を延長できますか？',
        ];

        return [
            'todo_id' => Todo::factory(),
            'user_id' => User::factory(),
            'body' => fake()->randomElement($comments),
        ];
    }
}
