# フェーズ20A: 全文検索エンジン導入 - 実装ガイド

## 概要
現在のLIKE検索を、Laravel Scout + Meilisearchによる全文検索に置き換えます。

## 現在の検索機能
- `app/Models/Todo.php` の `scopeSearch()` メソッド
- タイトル・内容を `LIKE '%keyword%'` で検索
- 日本語検索が弱い、関連度スコアなし

---

## 実装手順

### ステップ1: Laravel Scoutのインストール

```bash
composer require laravel/scout
php artisan vendor:publish --provider="Laravel\Scout\ScoutServiceProvider"
```

### ステップ2: Meilisearch Driverのインストール

```bash
composer require meilisearch/meilisearch-php http-interop/http-factory-guzzle
```

### ステップ3: Meilisearchサーバーのセットアップ（Docker推奨）

#### docker-compose.ymlに追加

```yaml
services:
  meilisearch:
    image: getmeili/meilisearch:latest
    ports:
      - "7700:7700"
    environment:
      MEILI_ENV: development
      MEILI_MASTER_KEY: your-master-key-here
    volumes:
      - meilisearch_data:/meili_data

volumes:
  meilisearch_data:
```

#### サーバー起動

```bash
docker-compose up -d meilisearch
```

### ステップ4: 環境変数設定（.env）

```env
SCOUT_DRIVER=meilisearch
MEILISEARCH_HOST=http://localhost:7700
MEILISEARCH_KEY=your-master-key-here
```

### ステップ5: Scoutの設定（config/scout.php）

以下の設定を確認・変更：

```php
'meilisearch' => [
    'host' => env('MEILISEARCH_HOST', 'http://localhost:7700'),
    'key' => env('MEILISEARCH_KEY'),
    'index-settings' => [
        'todos' => [
            'filterableAttributes' => ['category_id', 'priority', 'completed_at', 'user_id'],
            'sortableAttributes' => ['end_date', 'created_at', 'priority', 'title'],
            'searchableAttributes' => ['title', 'content'],
            'displayedAttributes' => ['*'],
        ],
    ],
],
```

### ステップ6: Todoモデルの変更

**app/Models/Todo.php**

```php
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Laravel\Scout\Searchable; // 追加

class Todo extends Model
{
    use HasFactory;
    use Searchable; // 追加

    // ... 既存のコード ...

    // ========================================
    // Scout設定
    // ========================================
    
    /**
     * インデックス可能なモデルのデータ配列を取得
     */
    public function toSearchableArray()
    {
        return [
            'id' => $this->id,
            'title' => $this->title,
            'content' => $this->content,
            'category_id' => $this->category_id,
            'priority' => $this->priority,
            'completed_at' => $this->completed_at,
            'user_id' => $this->user_id,
            'end_date' => $this->end_date?->timestamp,
            'created_at' => $this->created_at?->timestamp,
        ];
    }

    /**
     * インデックス対象の判定（完全削除されたものは除外）
     */
    public function shouldBeSearchable()
    {
        return true; // 必要に応じて条件を追加
    }

    // ========================================
    // スコープ（検索・絞り込み用）
    // ========================================
    
    /**
     * タイトル・内容検索（Scout使用）
     */
    public function scopeSearch($query, $keyword)
    {
        if ($keyword) {
            // Scout検索を使用
            // 注意: Scout検索はクエリビルダーではなく、Collectionを返す
            // そのため、既存の絞り込みと統合が必要
            return $query->whereIn('id', 
                self::search($keyword)
                    ->where('user_id', auth()->id())
                    ->get()
                    ->pluck('id')
            );
        }
        return $query;
    }

    // ... 他のスコープは変更なし ...
}
```

### ステップ7: 既存データのインデックス作成

```bash
# すべてのTodoをインデックスに追加
php artisan scout:import "App\Models\Todo"

# インデックスの確認
php artisan scout:flush "App\Models\Todo"  # インデックスをクリア（テスト用）
php artisan scout:import "App\Models\Todo"  # 再インポート
```

### ステップ8: 検索UIの確認

既存の検索フォーム（`resources/views/todos/index.blade.php`）はそのまま動作するはずです。

```blade
<form method="GET" action="{{ route('todos.index') }}">
    <input type="text" name="q" value="{{ request('q') }}" placeholder="検索...">
    <button type="submit">検索</button>
</form>
```

---

## 動作確認

### 1. Meilisearchサーバーの確認

```bash
curl http://localhost:7700/health
# 期待される応答: {"status":"available"}
```

### 2. インデックスの確認

ブラウザで以下にアクセス:
```
http://localhost:7700
```

または：

```bash
curl http://localhost:7700/indexes/todos
```

### 3. 検索テスト

アプリケーションで検索を実行:
1. https://todo-app.test/todos
2. 検索ボックスにキーワードを入力
3. 検索結果が表示されることを確認

---

## トラブルシューティング

### インデックスが作成されない

```bash
# ログを確認
tail -f storage/logs/laravel.log

# Scoutの接続確認
php artisan tinker
> App\Models\Todo::search('test')->get();
```

### Meilisearchサーバーに接続できない

```bash
# Dockerコンテナの確認
docker ps | grep meilisearch

# ログ確認
docker logs <container-id>

# 再起動
docker-compose restart meilisearch
```

### 検索結果が0件

```bash
# インデックスの再作成
php artisan scout:flush "App\Models\Todo"
php artisan scout:import "App\Models\Todo"

# インデックス数の確認
curl http://localhost:7700/indexes/todos/stats
```

---

## 次のステップ（フェーズ20A残タスク）

- [ ] 日本語形態素解析対応（Meilisearch設定）
- [ ] 検索結果のハイライト表示（UI実装）
- [ ] 検索パフォーマンスのモニタリング

---

## 参考リンク

- [Laravel Scout公式ドキュメント](https://laravel.com/docs/11.x/scout)
- [Meilisearch公式ドキュメント](https://www.meilisearch.com/docs)
- [Meilisearch PHP SDK](https://github.com/meilisearch/meilisearch-php)

---

作成日: 2026-04-28
