<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Todo extends Model
{
    use HasFactory;

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
        'image_path'
    ];

    protected $casts = [
        'start_date' => 'datetime',
        'end_date' => 'datetime',
        'completed_at' => 'datetime',
        'is_pinned' => 'boolean'
    ];

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

    // ========================================
    // スコープ（検索・絞り込み用）
    // ========================================
    /**
     * タイトル・内容検索
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param string|null $keyword
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeSearch($query, $keyword)
    {
        if ($keyword) {
            return $query->where(function ($query) use ($keyword) {
                $query->where('title', 'like', '%' . $keyword . '%')
                    ->orWhere('content', 'like', '%' . $keyword . '%');
            });
        } else {
            return $query;
        }
    }

    /**
     * カテゴリ絞り込み
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param int|null $categoryId
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeCategory($query, $categoryId)
    {
        if ($categoryId) {
            return $query->where('category_id', $categoryId);
        } else {
            return $query;
        }
    }

    /**
     * 優先度絞り込み
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param int|null $priority
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopePriority($query, $priority)
    {
        if ($priority) {
            return $query->where('priority', $priority);
        } else {
            return $query;
        }
    }

    /**
     * 期間指定検索
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param string|null $dateFrom
     * @param string|null $dateTo
     * @return \Illuminate\Database\Eloquent\Builder
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
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param string|null $filter 'active' | 'done' | null
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeCompletedFilter($query, $filter)
    {
        if ($filter) {
            if ($filter == 'active') {
                return $query->whereNull('completed_at');
            } elseif ($filter == 'done') {
                return $query->whereNotNull('completed_at');
            }
            return $query;
        } else {
            return $query;
        }
    }
}
