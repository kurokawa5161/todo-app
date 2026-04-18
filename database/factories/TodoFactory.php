<?php

namespace Database\Factories;

use App\Models\Todo;
use App\Models\User;
use App\Models\Category;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Todo>
 */
class TodoFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $titles = [
            '週次レポートを作成する',
            'プレゼン資料を準備する',
            'ミーティングの議事録をまとめる',
            'バグ修正を完了する',
            'コードレビューを依頼する',
            '新機能の設計書を作成する',
            '買い物リストを作成する',
            '歯医者の予約を取る',
            '本を読む（プログラミング関連）',
            'ジムに行く',
            'メールを返信する',
            'プロジェクトの進捗確認',
            'データベースの最適化',
            'テストコードを書く',
            'ドキュメントを更新する',
            '月次報告書を提出する',
            'クライアントとの打ち合わせ',
            'サーバーの保守作業',
            '領収書を整理する',
            '英語の勉強をする',
        ];

        $contents = [
            '明日までに完了する必要があります。詳細は別途確認します。',
            '優先度が高いので早めに対応します。',
            'チームメンバーと相談してから進めます。',
            '参考資料を確認してから作業を開始します。',
            '来週のミーティングまでに準備が必要です。',
            '詳細はSlackで共有されています。',
            '進捗状況を定期的に報告してください。',
            '必要に応じて追加のリソースを確保します。',
            '完了後、関係者に報告します。',
            '時間があるときに対応します。',
        ];

        return [
            'title' => fake()->randomElement($titles),
            'content' => fake()->randomElement($contents),
            'start_date' => $start_date = fake()->dateTimeBetween('-1 month', '+1 month'),
            'end_date' => fake()->dateTimeBetween($start_date, '+2 month'),
            'completed_at' => null,
            'user_id' => User::factory(),
            'category_id' => Category::factory(),
            'priority' => fake()->numberBetween(1, 3),
            'parent_id' => null,
            'is_pinned' => fake()->boolean(),
            'image_path' => fake()->optional()->filePath(),
        ];
    }
}
