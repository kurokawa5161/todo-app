# フェーズ20A: 設定ファイルサンプル

## 1. docker-compose.ymlへの追加

既存の`docker-compose.yml`に以下を追加：

```yaml
services:
  # ... 既存のサービス ...

  meilisearch:
    image: getmeili/meilisearch:v1.7
    container_name: todo-app-meilisearch
    ports:
      - "7700:7700"
    environment:
      MEILI_ENV: development
      MEILI_MASTER_KEY: masterKey123456789  # 本番環境では強力なキーに変更
      MEILI_NO_ANALYTICS: "true"
    volumes:
      - meilisearch_data:/meili_data
    restart: unless-stopped
    networks:
      - todo-network

volumes:
  # ... 既存のボリューム ...
  meilisearch_data:

networks:
  todo-network:
    driver: bridge
```

---

## 2. .env設定

`.env`ファイルに以下を追加：

```env
# Scout設定
SCOUT_DRIVER=meilisearch
SCOUT_QUEUE=false  # キューを使わない場合はfalse

# Meilisearch設定
MEILISEARCH_HOST=http://localhost:7700
MEILISEARCH_KEY=masterKey123456789
```

---

## 3. config/scout.php（完全版）

`php artisan vendor:publish --provider="Laravel\Scout\ScoutServiceProvider"`実行後、
以下のように`config/scout.php`を編集：

```php
<?php

return [

    'driver' => env('SCOUT_DRIVER', 'meilisearch'),

    'prefix' => env('SCOUT_PREFIX', ''),

    'queue' => env('SCOUT_QUEUE', false),

    'after_commit' => false,

    'chunk' => [
        'searchable' => 500,
        'unsearchable' => 500,
    ],

    'soft_delete' => false,

    'identify' => env('SCOUT_IDENTIFY', false),

    'meilisearch' => [
        'host' => env('MEILISEARCH_HOST', 'http://localhost:7700'),
        'key' => env('MEILISEARCH_KEY'),
        'index-settings' => [
            'todos' => [
                // 絞り込み可能な属性
                'filterableAttributes' => [
                    'category_id',
                    'priority',
                    'completed_at',
                    'user_id',
                    'end_date',
                    'created_at',
                ],
                
                // ソート可能な属性
                'sortableAttributes' => [
                    'end_date',
                    'created_at',
                    'priority',
                    'title',
                ],
                
                // 検索対象の属性（重要度順）
                'searchableAttributes' => [
                    'title',
                    'content',
                ],
                
                // 表示する属性
                'displayedAttributes' => ['*'],
                
                // ランキングルール
                'rankingRules' => [
                    'words',
                    'typo',
                    'proximity',
                    'attribute',
                    'sort',
                    'exactness',
                ],
                
                // タイポ許容設定
                'typoTolerance' => [
                    'enabled' => true,
                    'minWordSizeForTypos' => [
                        'oneTypo' => 5,
                        'twoTypos' => 9,
                    ],
                ],
            ],
        ],
    ],

    'algolia' => [
        'id' => env('ALGOLIA_APP_ID', ''),
        'secret' => env('ALGOLIA_SECRET', ''),
    ],

];
```

---

## 4. app/Models/Todo.php（完全版）

```php
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Laravel\Scout\Searchable;

class Todo extends Model
{
    use HasFactory;
    use Searchable;  // ← 追加

    protected $fillable = [
        'title',
        'content',
        'start_date',
        'end_date',
        'category_id',
        'completed_at',
        'priority',
        'parent_id',
        'is_pinned',
        'image_path',
        'team_id',
        'github_issue_url',
    ];

    protected $casts = [
        'start_date' => 'datetime',
        'end_date' => 'datetime',
        'completed_at' => 'datetime',
        'is_pinned' => 'boolean'
    ];

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
            'completed_at' => $this->completed_at?->timestamp,
            'user_id' => $this->user_id,
            'end_date' => $this->end_date?->timestamp,
            'created_at' => $this->created_at?->timestamp,
        ];
    }

    /**
     * インデックス名をカスタマイズ（オプション）
     */
    public function searchableAs()
    {
        return 'todos';
    }

    /**
     * インデックス対象の判定
     */
    public function shouldBeSearchable()
    {
        // 削除されていないTodoのみインデックス
        return true;
    }

    // ========================================
    // リレーション
    // ========================================
    
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function category()
    {
        return $this->belongsTo(Category::class);
    }

    public function parent()
    {
        return $this->belongsTo(Todo::class, 'parent_id');
    }

    public function children()
    {
        return $this->hasMany(Todo::class, 'parent_id');
    }

    public function comments()
    {
        return $this->hasMany(Comment::class);
    }

    public function tags()
    {
        return $this->belongsToMany(Tag::class, 'todo_tag');
    }

    public function team()
    {
        return $this->belongsTo(Team::class);
    }

    public function assignedUser()
    {
        return $this->belongsTo(User::class, 'assigned_to');
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
            // Scout検索を使用してIDを取得
            $searchResults = self::search($keyword)
                ->where('user_id', auth()->id())
                ->get()
                ->pluck('id')
                ->toArray();
            
            // 検索結果が0件の場合は空のクエリを返す
            if (empty($searchResults)) {
                return $query->whereRaw('1 = 0');
            }
            
            // IDで絞り込み
            return $query->whereIn('id', $searchResults);
        }
        return $query;
    }

    /**
     * カテゴリ絞り込み
     */
    public function scopeCategory($query, $categoryId)
    {
        if ($categoryId) {
            return $query->where('category_id', $categoryId);
        }
        return $query;
    }

    /**
     * タグ絞り込み
     */
    public function scopeTag($query, $tagId)
    {
        if ($tagId) {
            return $query->whereHas('tags', function ($q) use ($tagId) {
                $q->where('tags.id', $tagId);
            });
        }
        return $query;
    }

    /**
     * 優先度絞り込み
     */
    public function scopePriority($query, $priority)
    {
        if ($priority) {
            return $query->where('priority', $priority);
        }
        return $query;
    }

    /**
     * 期間指定検索
     */
    public function scopeDateRange($query, $dateFrom, $dateTo)
    {
        if ($dateFrom && $dateTo) {
            $query->where('end_date', '>=', $dateFrom);
            $query->where('end_date', '<=', $dateTo);
        } elseif ($dateFrom) {
            $query->where('end_date', '>=', $dateFrom);
        } elseif ($dateTo) {
            $query->where('end_date', '<=', $dateTo);
        }
        return $query;
    }

    /**
     * 完了状態フィルター
     */
    public function scopeCompletedFilter($query, $filter)
    {
        if ($filter) {
            if ($filter == 'active') {
                return $query->whereNull('completed_at');
            } elseif ($filter == 'done') {
                return $query->whereNotNull('completed_at');
            }
        }
        return $query;
    }
}
```

---

## 5. テスト用コマンド集

### インデックス作成・削除

```bash
# インデックスを空にする
php artisan scout:flush "App\Models\Todo"

# データをインポート
php artisan scout:import "App\Models\Todo"

# 特定のTodoをインデックスに追加
php artisan tinker
> $todo = App\Models\Todo::find(1);
> $todo->searchable();

# 特定のTodoをインデックスから削除
> $todo->unsearchable();
```

### Meilisearch APIで直接確認

```bash
# インデックス一覧
curl http://localhost:7700/indexes

# todosインデックスの詳細
curl http://localhost:7700/indexes/todos

# todosインデックスの統計
curl http://localhost:7700/indexes/todos/stats

# 検索テスト
curl -X POST 'http://localhost:7700/indexes/todos/search' \
  -H 'Content-Type: application/json' \
  --data-binary '{"q": "テスト"}'
```

---

## 6. 本番環境への注意事項

### セキュリティ
- `MEILI_MASTER_KEY`は強力なランダム文字列に変更
- 本番環境では`MEILI_ENV=production`に設定
- Meilisearchポート（7700）は外部に公開しない

### パフォーマンス
- `SCOUT_QUEUE=true`にしてキュー経由でインデックス更新
- キューワーカーを起動: `php artisan queue:work`

### バックアップ
- Meilisearchデータは`meilisearch_data`ボリュームに保存
- 定期的にボリュームをバックアップ

```bash
# ボリュームのバックアップ
docker run --rm -v meilisearch_data:/data -v $(pwd):/backup ubuntu tar czf /backup/meilisearch_backup.tar.gz /data
```

---

作成日: 2026-04-28
