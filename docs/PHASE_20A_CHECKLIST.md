# フェーズ20A: 全文検索エンジン導入 - チェックリスト

## 実装タスク

### 1. 環境セットアップ
- [ ] Laravel Scoutのインストール (`composer require laravel/scout`)
- [ ] Scout設定ファイルの公開 (`php artisan vendor:publish --provider="Laravel\Scout\ScoutServiceProvider"`)
- [ ] Meilisearch PHPクライアントのインストール (`composer require meilisearch/meilisearch-php http-interop/http-factory-guzzle`)

### 2. Meilisearchサーバー
- [ ] `docker-compose.yml`にMeilisearchサービス追加
- [ ] Meilisearchコンテナ起動 (`docker-compose up -d meilisearch`)
- [ ] サーバー疎通確認 (`curl http://localhost:7700/health`)

### 3. 設定ファイル
- [ ] `.env`にScout設定追加（SCOUT_DRIVER, MEILISEARCH_HOST, MEILISEARCH_KEY）
- [ ] `config/scout.php`のMeilisearch設定確認
  - [ ] filterableAttributes設定
  - [ ] sortableAttributes設定
  - [ ] searchableAttributes設定

### 4. Todoモデル変更
- [ ] `use Laravel\Scout\Searchable;` トレイト追加
- [ ] `toSearchableArray()` メソッド実装
- [ ] `shouldBeSearchable()` メソッド実装（必要に応じて）
- [ ] `scopeSearch()` メソッドをScout対応に変更

### 5. インデックス作成
- [ ] 既存データをインデックスに登録 (`php artisan scout:import "App\Models\Todo"`)
- [ ] インデックス作成確認 (`curl http://localhost:7700/indexes/todos`)
- [ ] インデックス統計確認 (`curl http://localhost:7700/indexes/todos/stats`)

### 6. 動作確認
- [ ] ブラウザで検索テスト（https://todo-app.test/todos）
- [ ] 日本語キーワードで検索
- [ ] 英語キーワードで検索
- [ ] 部分一致検索の確認
- [ ] 検索結果が正しく表示されることを確認

### 7. 日本語形態素解析対応（オプション）
- [ ] Meilisearchの日本語トークナイザー設定
- [ ] 日本語検索のテスト強化
- [ ] ひらがな・カタカナ・漢字の検索確認

### 8. 検索結果ハイライト表示
- [ ] ハイライト用のフロントエンド実装
- [ ] Meilisearch APIからハイライト情報取得
- [ ] 検索結果画面でハイライト表示

---

## トラブルシューティング

### インデックスが作成されない場合
```bash
# ログ確認
tail -f storage/logs/laravel.log

# インデックス再作成
php artisan scout:flush "App\Models\Todo"
php artisan scout:import "App\Models\Todo"
```

### Meilisearchサーバーに接続できない場合
```bash
# コンテナ確認
docker ps | grep meilisearch

# ログ確認
docker logs <container-id>

# 再起動
docker-compose restart meilisearch
```

---

## 完了条件

✅ フェーズ20A完了の条件：
1. Meilisearchサーバーが正常起動している
2. Todoモデルに`Searchable`トレイトが追加されている
3. 既存データがすべてインデックスに登録されている
4. ブラウザから日本語・英語の検索が正常に動作する
5. 検索結果が関連度順にソートされている

---

## 次のステップ

フェーズ20B: 高度な検索機能
- ファセット検索（カテゴリ、タグ、期限での絞り込み）
- 検索履歴の保存
- サジェスト機能
- 検索結果のソート（関連度、日付、優先度）

---

作成日: 2026-04-28
進捗: 0/8 タスク完了
