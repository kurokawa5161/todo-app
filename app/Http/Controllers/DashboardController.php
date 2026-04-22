<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Todo;
use App\Models\Category;

class DashboardController extends Controller
{
    public function index()
    {
        //総Todo数、完了数、未完了数
        $total = Todo::where('user_id', auth()->id())->count();
        $done = Todo::where('user_id', auth()->id())->whereNotNull('completed_at')->count();
        $active = Todo::where('user_id', auth()->id())->whereNull('completed_at')->count();

        //完了率（全体、今週、今月）
        $completedAll =  $total > 0 ? $done * 100 / $total : 0;

        // 今週（データベース非依存）
        $week = Todo::where('user_id', auth()->id())
            ->whereNotNull('completed_at')
            ->whereBetween('end_date', [now()->startOfWeek(), now()->endOfWeek()])
            ->count();
        $deadlineWeek = Todo::where('user_id', auth()->id())
            ->whereBetween('end_date', [now()->startOfWeek(), now()->endOfWeek()])
            ->count();
        $completedWeek = $deadlineWeek > 0 ? $week * 100 / $deadlineWeek : 0;

        // 今月（データベース非依存）
        $month = Todo::where('user_id', auth()->id())
            ->whereNotNull('completed_at')
            ->whereBetween('end_date', [now()->startOfMonth(), now()->endOfMonth()])
            ->count();
        $deadlineMonth = Todo::where('user_id', auth()->id())
            ->whereBetween('end_date', [now()->startOfMonth(), now()->endOfMonth()])
            ->count();
        $completedMonth = $deadlineMonth > 0 ? $month * 100 / $deadlineMonth : 0;

        //期限遵守率
        //期限があるTodo
        $deadLineTodo = Todo::where('user_id', auth()->id())->whereNotNull('end_date')->count();
        //期限内完了Todo
        $deadLineCompTodo = Todo::where('user_id', auth()->id())->whereNotNull('end_date')
            ->whereNotNull('completed_at')->whereColumn('completed_at', '<=', 'end_date')->count();
        $deadLineCompTodoPct = $deadLineTodo > 0 ? $deadLineCompTodo / $deadLineTodo * 100 : 0;

        //カテゴリ別集計（カテゴリごとの総数、完了、未完了、完了率）
        $categories = array();
        $categoryTotals = Todo::selectRaw('todos.category_id, categories.name as category_name, count(*) as total')
            ->leftjoin('categories', 'todos.category_id', '=', 'categories.id')
            ->where('todos.user_id', auth()->id())
            ->groupBy('todos.category_id', 'categories.name')->get();
        $categoryDones =  Todo::selectRaw('category_id, count(*) as done')
            ->leftjoin('categories', 'todos.category_id', '=', 'categories.id')
            ->where('todos.user_id', auth()->id())
            ->whereNotNull('todos.completed_at')
            ->groupBy('todos.category_id')
            ->pluck('done', 'category_id'); // category_id をキーにした配列
        $categoryActives = Todo::selectRaw('category_id, count(*) as active')
            ->leftjoin('categories', 'todos.category_id', '=', 'categories.id')
            ->where('todos.user_id', auth()->id())
            ->whereNull('todos.completed_at')
            ->groupBy('todos.category_id')
            ->pluck('active', 'category_id'); // category_id をキーにした配列
        foreach ($categoryTotals as $categoryTotal) {
            $done = $categoryDones[$categoryTotal->category_id] ?? 0;
            $categories[] = [
                'category_id' => $categoryTotal->category_id,
                'category_name' => $categoryTotal->category_name,
                'total' => $categoryTotal->total,
                'done' => $done,
                'active' => $categoryActives[$categoryTotal->category_id] ?? 0,
                'completed' => $categoryTotal->total > 0 ? $done / $categoryTotal->total * 100 : 0,
            ];
        }

        //タグ別集計
        $tags = array();
        $tagTotals = Todo::selectRaw('tags.id as tag_id, tags.name as tag_name, count(*) as total')
            ->join('todo_tag', 'todos.id', '=', 'todo_tag.todo_id')
            ->join('tags', 'todo_tag.tag_id', '=', 'tags.id')
            ->where('todos.user_id', auth()->id())
            ->groupBy('tags.id', 'tags.name')->get();
        $tagDones =  Todo::selectRaw('tags.id as tag_id, count(*) as done')
            ->join('todo_tag', 'todos.id', '=', 'todo_tag.todo_id')
            ->join('tags', 'todo_tag.tag_id', '=', 'tags.id')
            ->where('todos.user_id', auth()->id())
            ->whereNotNull('todos.completed_at')
            ->groupBy('tags.id')
            ->pluck('done', 'tag_id'); // tags.id をキーにした配列
        $tagActives = Todo::selectRaw('tags.id as tag_id, count(*) as active')
            ->join('todo_tag', 'todos.id', '=', 'todo_tag.todo_id')
            ->join('tags', 'todo_tag.tag_id', '=', 'tags.id')
            ->where('todos.user_id', auth()->id())
            ->whereNull('todos.completed_at')
            ->groupBy('tags.id')
            ->pluck('active', 'tag_id'); // tags.id をキーにした配列
        foreach ($tagTotals as $tagTotal) {
            $done = $tagDones[$tagTotal->tag_id] ?? 0;
            $tags[] = [
                'tag_id' => $tagTotal->tag_id,
                'tag_name' => $tagTotal->tag_name,
                'total' => $tagTotal->total,
                'done' => $done,
                'active' => $tagActives[$tagTotal->tag_id] ?? 0,
                'completed' => $tagTotal->total > 0 ? $done / $tagTotal->total * 100 : 0,
            ];
        }

        //優先度別集計(１：高、２：中、３：低)
        $priorities = array();
        $priorityTotals = Todo::selectRaw('priority, count(*) as total')
            ->where('user_id', auth()->id())
            ->groupBy('priority')->get();
        $priorityDones =  Todo::selectRaw('priority, count(*) as done')
            ->where('user_id', auth()->id())
            ->whereNotNull('completed_at')
            ->groupBy('priority')
            ->pluck('done', 'priority'); // priority をキーにした配列
        $priorityActives = Todo::selectRaw('priority, count(*) as active')
            ->where('user_id', auth()->id())
            ->whereNull('completed_at')
            ->groupBy('priority')
            ->pluck('active', 'priority'); // priority をキーにした配列
        foreach ($priorityTotals as $priorityTotal) {
            $done = $priorityDones[$priorityTotal->priority] ?? 0;
            $priority_name = '';
            switch ($priorityTotal->priority) {
                case 1:
                    $priority_name = '高';
                    break;
                case 2:
                    $priority_name = '中';
                    break;
                case 3:
                    $priority_name = '低';
                    break;
                default:
                    $priority_name = '中';
                    break;
            }
            $priorities[] = [
                'priority' => $priorityTotal->priority,
                'priority_name' => $priority_name,
                'total' => $priorityTotal->total,
                'done' => $done,
                'active' => $priorityActives[$priorityTotal->priority] ?? 0,
                'completed' => $priorityTotal->total > 0 ? $done / $priorityTotal->total * 100 : 0,
            ];
        }


        $result = [
            'total' => $total,
            'done' => $done,
            'active' => $active,
            'completed_all' => $completedAll,
            'completed_week' => $completedWeek,
            'completed_month' => $completedMonth,
            'deadline_comp_todo_pct' => $deadLineCompTodoPct,
            'categories' => $categories,
            'tags' => $tags,
            'priorities' => $priorities,
        ];

        return view('dashboard', $result);
    }
}
