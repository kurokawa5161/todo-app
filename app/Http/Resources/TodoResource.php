<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class TodoResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            //基本情報
            'id' => $this->id,
            'title' => $this->title,
            'content' => $this->content,

            //日付
            'start_date' => $this->start_date,
            'end_date' => $this->end_date,
            'completed_at' => $this->completed_at,

            //その他
            'priority' => $this->priority,
            'is_pinned' => $this->is_pinned,

            //画像（パスをフルURLに変換）
            'image_url' => $this->image_path ? asset('storage/' . $this->image_path) : null,

            //カテゴリ
            'category' => $this->category ? [
                'id' => $this->category->id,
                'name' => $this->category->name,
                'color' => $this->category->color
            ] : null,

            //タグ
            'tags' => $this->tags->map(function ($tag) {
                return [
                    'id' => $tag->id,
                    'name' => $tag->name,
                    'color' => $tag->color
                ];
            }),

            //サブタスク
            'children' => $this->children->map(function ($child) {
                return [
                    'id' => $child->id,
                    'title' => $child->title,
                    'completed_at' => $child->completed_at
                ];
            }),

            //タイムスタンプ
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,

        ];
    }
}
