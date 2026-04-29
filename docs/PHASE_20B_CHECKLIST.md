# フェーズ20B: 高度な検索機能 - チェックリスト

## 実装タスク

### 1. Meilisearch設定の同期
- [ ] カスタムArtisanコマンド作成 (`app/Console/Commands/SyncMeilisearchSettings.php`)
- [ ] コマンド実行 (`php artisan scout:sync-index-settings`)
- [ ] filterableAttributesの確認（Git Bashで確認コマンド実行）

### 2. ファセット検索の実装
- [ ] `app/Http/Controllers/TodoController.php`の`index()`メソッド修正
  - [ ] 検索時のフィルター適用（category_id, priority, user_id）
  - [ ] 検索時のソート適用
- [ ] 動作確認（ブラウザで検索+絞り込みテスト）

### 3. 検索履歴の保存
- [ ] マイグレーション作成 (`create_search_histories_table`)
- [ ] `app/Models/SearchHistory.php`モデル作成
- [ ] マイグレーション実行 (`php artisan migrate`)
- [ ] TodoControllerで検索履歴保存処理追加
- [ ] 動作確認（検索後にDBを確認）

### 4. サジェスト機能
- [ ] API Controller作成 (`app/Http/Controllers/Api/SearchSuggestController.php`)
- [ ] ルート追加 (`routes/web.php` or `routes/api.php`)
- [ ] フロントエンド実装（Alpine.jsまたはVue.js）
  - [ ] 入力時のサジェスト表示
  - [ ] デバウンス処理（300ms）
  - [ ] サジェスト選択時の動作
- [ ] 動作確認（検索ボックスで入力テスト）

### 5. 検索結果のソート
- [ ] TodoControllerでソート処理追加
  - [ ] 関連度順（デフォルト）
  - [ ] 期限順
  - [ ] 作成日順
  - [ ] 優先度順
- [ ] ビューにソート選択UI追加
- [ ] 動作確認（各ソートオプションをテスト）

### 6. UI/UX改善（オプション）
- [ ] 検索履歴の表示UI
- [ ] 検索結果のハイライト表示
- [ ] 検索中のローディング表示
- [ ] 検索結果0件時のメッセージ

---

## トラブルシューティング

### filterableAttributesが空の場合
```bash
php artisan scout:sync-index-settings
```

### 検索が遅い場合
- Meilisearchサーバーのログ確認
- インデックスサイズ確認
- キャッシュの活用を検討

### サジェストが表示されない場合
- ブラウザのコンソールでエラー確認
- APIエンドポイントの確認（/api/search/suggest）
- 認証ミドルウェアの確認

---

## 完了条件

✅ フェーズ20B完了の条件：
1. カテゴリ・優先度での絞り込みが検索結果内で動作
2. 検索履歴がデータベースに保存されている
3. サジェスト機能が動作している（2文字以上入力で候補表示）
4. 検索結果のソートが動作している

---

## 次のステップ

フェーズ21: レポート機能の強化
- 週次サマリー（完了率、生産性グラフ）
- 月次レポート（カテゴリ別、タグ別分析）

---

作成日: 2026-04-29
進捗: 0/6 タスク完了
