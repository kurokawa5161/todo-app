<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Response;
use App\Models\Todo;
use App\Models\Category;
use App\Models\DashboardWidget;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Alignment;

class DashboardController extends Controller
{
    public function index()
    {
        $userId = auth()->id();
        //統計データは１時間キャッシュ
        $weeklyData = Cache::remember("weekly_data_{$userId}", 86400, function () {
            return $this->getWeeklyCompletionData();
        });
        $monthlyData = Cache::remember("monthly_data_{$userId}", 604800, function () {
            return $this->getMonthlyCompletionData();
        });
        $yearlyData = Cache::remember("yearly_data_{$userId}", 604800, function () {
            return $this->getYearlyCompletionData();
        });
        $heatmapData = Cache::remember("heatmap_data_{$userId}", 300, function () {
            return $this->getHeatmapData();
        });
        $gantData = Cache::remember("gant_data_{$userId}", 300, function () {
            return $this->getgantData();
        });



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
        //全Todo（end_dateは必須）
        $deadLineTodo = Todo::where('user_id', auth()->id())->count();
        //期限内完了Todo
        $deadLineCompTodo = Todo::where('user_id', auth()->id())
            ->whereNotNull('completed_at')->whereColumn('completed_at', '<=', 'end_date')->count();
        $deadLineCompTodoPct = $deadLineTodo > 0 ? $deadLineCompTodo / $deadLineTodo * 100 : 0;

        //カテゴリ別集計（カテゴリごとの総数、完了、未完了、完了率）
        $categoryStats = array();
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
            $categoryStats[] = [
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
            ->whereNotNull('priority')
            ->groupBy('priority')->get();
        $priorityDones =  Todo::selectRaw('priority, count(*) as done')
            ->where('user_id', auth()->id())
            ->whereNotNull('priority')
            ->whereNotNull('completed_at')
            ->groupBy('priority')
            ->pluck('done', 'priority'); // priority をキーにした配列
        $priorityActives = Todo::selectRaw('priority, count(*) as active')
            ->where('user_id', auth()->id())
            ->whereNotNull('priority')
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

        //カテゴリー
        $categories = Category::where('user_id', auth()->id())->get();

        //ウィジェット
        $widgets = DashboardWidget::where('user_id', auth()->id())
            ->orderBy('position', 'asc')->get();

        // デフォルトウィジェット自動作成（初回アクセス時）
        if ($widgets->count() === 0) {
            $defaultWidgets = [
                ['widget_type' => 'stats', 'position' => 0, 'size' => 'full'],
                ['widget_type' => 'chart_weekly', 'position' => 1, 'size' => 'medium'],
                ['widget_type' => 'chart_monthly', 'position' => 2, 'size' => 'medium'],
                ['widget_type' => 'heatmap', 'position' => 3, 'size' => 'medium'],
                ['widget_type' => 'gantt', 'position' => 4, 'size' => 'full'],
                ['widget_type' => 'recent_todos', 'position' => 5, 'size' => 'medium'],
                ['widget_type' => 'category_summary', 'position' => 6, 'size' => 'medium'],
                ['widget_type' => 'priority_summary', 'position' => 7, 'size' => 'medium'],
            ];

            foreach ($defaultWidgets as $widget) {
                DashboardWidget::create([
                    'user_id' => auth()->id(),
                    'widget_type' => $widget['widget_type'],
                    'position' => $widget['position'],
                    'size' => $widget['size'],
                    'is_visible' => true,
                    'settings' => null,
                ]);
            }

            $widgets = DashboardWidget::where('user_id', auth()->id())
                ->orderBy('position', 'asc')->get();
        }

        $result = [
            'total' => $total,
            'done' => $done,
            'active' => $active,
            'completed_all' => $completedAll,
            'completed_week' => $completedWeek,
            'completed_month' => $completedMonth,
            'deadline_comp_todo_pct' => $deadLineCompTodoPct,
            'categoryStats' => $categoryStats,
            'tags' => $tags,
            'priorities' => $priorities,
            'weeklyData' => $weeklyData,
            'monthlyData' => $monthlyData,
            'yearlyData' => $yearlyData,
            'heatmapData' => $heatmapData,
            'gantData' => $gantData,
            'categories' => $categories,
            'widgets' => $widgets
        ];

        return view('dashboard', $result);
    }

    //CSVエクスポート
    public function exportCsv()
    {
        $todos = Todo::where('user_id', auth()->id())
            ->with(['category', 'tags'])->get();

        $callback = function () use ($todos) {
            $file = fopen('php://output', 'w');

            //BOM追加（Excel用）
            fprintf($file, chr(0xEF) . chr(0xBB) . chr(0xBF));

            //ヘッダー行
            fputcsv($file, ['ID', 'タイトル', '内容', 'カテゴリー', 'タグ', '優先度', '開始日', '終了日', '完了日', 'ステータス']);

            foreach ($todos as $todo) {
                fputcsv($file, [
                    $todo->id,
                    $todo->title,
                    $todo->content,
                    $todo->category->name ?? '未分類',
                    $todo->tags->pluck('name')->join(', '),
                    $todo->priority == 1 ? '高' : ($todo->priority == 2 ? '中' : '低'),
                    $todo->start_date?->format('Y-m-d'),
                    $todo->end_date->format('Y-m-d'),
                    $todo->completed_at?->format('Y-m-d'),
                    $todo->completed_at ? '完了' : '未完了',
                ]);
            }
            fclose($file);
        };

        return Response::stream($callback, 200, [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename="todos_' . date('YmdHis') . '.csv"',
        ]);
    }

    //週次PDFレポート
    public function exportWeeklyPdf()
    {
        $startOfWeek = now()->startOfWeek();
        $endOfWeek = now()->endOfWeek();

        $data = [
            'title' => '週次レポート',
            'period' => $startOfWeek->format('Y-m-d') . '-' . $endOfWeek->format('Y-m-d'),
            'total' => Todo::where('user_id', auth()->id())->count(),
            'done' => Todo::where('user_id', auth()->id())->whereNotNull('completed_at')->count(),
            'active' => Todo::where('user_id', auth()->id())->whereNull('completed_at')->count(),
            'weekly_completed' =>  Todo::where('user_id', auth()->id())
                ->whereNotNull('completed_at')
                ->whereBetween('completed_at', [now()->startOfWeek(), now()->endOfWeek()])
                ->count(),
        ];

        $pdf = Pdf::loadView('reports.weekly', $data);
        return $pdf->download('weekly_report_' . date('YmdHis') . '.pdf');
    }

    //月次レポート
    public function exportMonthlyPdf()
    {
        $startOfMonth = now()->startOfMonth();
        $endOfMonth = now()->endOfMonth();

        $data = [
            'title' => '月次レポート',
            'period' => $startOfMonth->format('Y-m-d') . '-' . $endOfMonth->format('Y-m-d'),
            'total' => Todo::where('user_id', auth()->id())->count(),
            'done' => Todo::where('user_id', auth()->id())->whereNotNull('completed_at')->count(),
            'active' => Todo::where('user_id', auth()->id())->whereNull('completed_at')->count(),
            'monthly_completed' =>  Todo::where('user_id', auth()->id())
                ->whereNotNull('completed_at')
                ->whereBetween('end_date', [now()->startOfMonth(), now()->endOfMonth()])
                ->count(),
        ];
        $pdf = Pdf::loadView('reports.monthly', $data);
        return $pdf->download('monthly_report_' . date('YmdHis') . '.pdf');
    }

    //年間レポート
    public function exportYearlyPdf()
    {
        $startOfYear = now()->subYear()->startOfDay();
        $endOfYear = now()->endOfDay();

        $data = [
            'title' => '年間レポート',
            'period' => $startOfYear->format('Y-m-d') . '-' . $endOfYear->format('Y-m-d'),
            'total' => Todo::where('user_id', auth()->id())->count(),
            'done' => Todo::where('user_id', auth()->id())->whereNotNull('completed_at')->count(),
            'active' => Todo::where('user_id', auth()->id())->whereNull('completed_at')->count(),
            'yearly_completed' =>  Todo::where('user_id', auth()->id())
                ->whereNotNull('completed_at')
                ->whereBetween('completed_at', [$startOfYear, $endOfYear])
                ->count(),
        ];
        $pdf = Pdf::loadView('reports.yearly', $data);
        return $pdf->download('yearly_report_' . date('YmdHis') . '.pdf');
    }

    //週次データ取得
    private function getWeeklyCompletionData()
    {
        $labels = [];
        $data = [];

        // 過去4週間の週次完了数
        for ($i = 3; $i >= 0; $i--) {
            $start = now()->subWeeks($i)->startOfWeek();
            $end = now()->subWeeks($i)->endOfWeek();

            $count = Todo::where('user_id', auth()->id())
                ->whereNotNull('completed_at')
                ->whereBetween('completed_at', [$start, $end])
                ->count();

            $labels[] = '第' . (4 - $i) . '週';
            $data[] = $count;
        }

        return [
            'labels' => $labels,
            'data' => $data
        ];
    }

    //月次データ取得
    private function getMonthlyCompletionData()
    {
        $labels = [];
        $data = [];

        // 過去6ヶ月の月次完了数
        for ($i = 5; $i >= 0; $i--) {
            $start = now()->subMonths($i)->startOfMonth();
            $end = now()->subMonths($i)->endOfMonth();

            $count = Todo::where('user_id', auth()->id())
                ->whereNotNull('completed_at')
                ->whereBetween('completed_at', [$start, $end])
                ->count();

            $labels[] = $start->format('Y-m');
            $data[] = $count;
        }

        return [
            'labels' => $labels,
            'data' => $data
        ];
    }

    //年間データ取得
    private function getYearlyCompletionData()
    {
        $labels = [];
        $data = [];

        for ($i = 11; $i >= 0; $i--) {
            $start = now()->subMonths($i)->startOfMonth();
            $end = now()->subMonths($i)->endOfMonth();

            $count = Todo::where('user_id', auth()->id())
                ->whereNotNull('completed_at')
                ->whereBetween('completed_at', [$start, $end])
                ->count();

            $labels[] = $start->format('Y-m');
            $data[] = $count;
        }

        return [
            'labels' => $labels,
            'data' => $data
        ];
    }

    //ヒートマップデータ取得（過去30日間）
    private function getHeatmapData()
    {
        // 過去30日間のデータを取得
        $rawData = [];
        for ($i = 29; $i >= 0; $i--) {
            $date = now()->subDays($i);
            $dateStr = $date->format('Y-m-d');

            $count = Todo::where('user_id', auth()->id())
                ->whereNotNull('completed_at')
                ->whereRaw('DATE(completed_at) = ?', [$dateStr])
                ->count();

            $rawData[] = [
                'date' => $dateStr,
                'dayOfWeek' => (int)$date->format('w'), // 0(日)～6(土)
                'count' => $count,
            ];
        }

        // 曜日ごとにカレンダー形式に変換
        $calendar = array_fill(0, 7, []); // 7曜日分の配列
        foreach ($rawData as $data) {
            $calendar[$data['dayOfWeek']][] = [
                'date' => $data['date'],
                'count' => $data['count'],
            ];
        }

        // 月ラベルを生成（最初の週の月を抽出）
        $months = [];
        foreach ($calendar[0] as $cell) {
            $month = date('n月', strtotime($cell['date']));
            if (!in_array($month, $months)) {
                $months[] = $month;
            }
        }

        return [
            'months' => $months,
            'calendar' => $calendar,
        ];
    }


    private function getgantData()
    {
        return Todo::where('user_id', auth()->id())
            ->select('id', 'title', 'start_date', 'end_date', 'completed_at', 'category_id')
            ->with('category:id,name,color')
            ->whereNotNull('start_date')
            ->whereNotNull('end_date')
            ->orderBy('start_date')
            ->get()
            ->filter(function ($todo) {
                // 開始日が終了日より前であることを確認
                return $todo->start_date <= $todo->end_date;
            })
            ->map(function ($todo) {
                return [
                    'id' => 'task-' . $todo->id,
                    'name' => $todo->title,
                    'start' => $todo->start_date->format('Y-m-d'),
                    'end' => $todo->end_date->format('Y-m-d'),
                    'progress' => $todo->completed_at ? 100 : 0,
                    'custom_class' => $todo->category ? 'gantt-category-' . $todo->category->id : 'gantt-default',
                    'category_color' => $todo->category?->color ?? '#94a3b8',
                ];
            })
            ->values()
            ->toArray();
    }

    public function exportExcel()
    {
        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();

        // タイトル行
        $sheet->setCellValue('A1', 'Todoリスト');
        $sheet->mergeCells('A1:J1');
        $sheet->getStyle('A1')->getFont()->setBold(true)->setSize(16);
        $sheet->getStyle('A1')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

        // ヘッダー行（3行目）
        $headers = ['ID', 'タイトル', '内容', 'カテゴリー', 'タグ', '優先度', '開始日', '終了日', '完了日', 'ステータス'];
        $column = 'A';
        foreach ($headers as $header) {
            $sheet->setCellValue($column . '3', $header);
            $sheet->getStyle($column . '3')->getFont()->setBold(true);
            $sheet->getStyle($column . '3')->getFill()
                ->setFillType(Fill::FILL_SOLID)
                ->getStartColor()->setARGB('FFE0E0E0');
            $column++;
        }

        // データ取得
        $todos = Todo::where('user_id', auth()->id())
            ->with(['category', 'tags'])
            ->orderBy('end_date')
            ->get();

        if ($todos->isEmpty()) {
            $sheet->setCellValue('A4', 'データがありません');
            $sheet->mergeCells('A4:J4');
        }

        // データ行
        $row = 4;
        foreach ($todos as $todo) {
            $sheet->setCellValue('A' . $row, $todo->id);
            $sheet->setCellValue('B' . $row, $todo->title);
            $sheet->setCellValue('C' . $row, $todo->content);
            $sheet->setCellValue('D' . $row, $todo->category->name ?? '未分類');
            $sheet->setCellValue('E' . $row, $todo->tags->pluck('name')->join(', '));
            $sheet->setCellValue('F' . $row, $todo->priority == 1 ? '高' : ($todo->priority == 2 ? '中' : '低'));
            $sheet->setCellValue('G' . $row, $todo->start_date->format('Y-m-d'));
            $sheet->setCellValue('H' . $row, $todo->end_date->format('Y-m-d'));
            $sheet->setCellValue('I' . $row, $todo->completed_at?->format('Y-m-d') ?? '');
            $sheet->setCellValue('J' . $row, $todo->completed_at ? '完了' : '未完了');
            $row++;
        }

        // 列幅自動調整
        foreach (range('A', 'J') as $col) {
            $sheet->getColumnDimension($col)->setAutoSize(true);
        }

        // 枠線追加
        $sheet->getStyle('A3:J' . ($row - 1))
            ->getBorders()
            ->getAllBorders()
            ->setBorderStyle(Border::BORDER_THIN);

        // ファイル出力
        $writer = new Xlsx($spreadsheet);
        $fileName = 'todos_' . date('YmdHis') . '.xlsx';

        return response()->streamDownload(function () use ($writer) {
            $writer->save('php://output');
        }, $fileName, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        ]);
    }

    //JSONエクスポート
    public function exportJson()
    {
        $todos = Todo::Where('user_id', auth()->id())
            ->with(['category', 'tags'])
            ->orderBy('end_date')
            ->get()
            ->map(function ($todo) {
                return [
                    'id' => $todo->id,
                    'title' => $todo->title,
                    'content' => $todo->content,
                    'category' => $todo->category?->name,
                    'tags' => $todo->tags->pluck('name'),
                    'priority' => match ($todo->priority) {
                        1 => '高',
                        2 => '中',
                        3 => '低',
                        default => ''
                    },
                    'start_date' => $todo->start_date->format('Y-m-d'),
                    'end_date' => $todo->end_date->format('Y-m-d'),
                    'completed_at' => $todo->completed_at?->format('Y-m-d'),
                    'status' => $todo->completed_at ? '完了' : '未完了'
                ];
            });

        $fileName = 'todos_' . date('YmdHis') . '.json';

        return response()->json($todos, 200, [
            'Content-Type' => 'application/json',
            'Content-Disposition' => 'attachment; filename="' . $fileName . '"',
        ], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
    }

    //XMLエクスポート
    public function exportXml()
    {
        $todos = Todo::Where('user_id', auth()->id())
            ->with(['category', 'tags'])
            ->orderBy('end_date')
            ->get();

        $xml = new \SimpleXMLElement('<?xml version="1.0" encoding="UTF-8"?><todos/>');

        foreach ($todos as $todo) {
            $todoNode = $xml->addChild('todo');
            $todoNode->addChild('id', $todo->id);
            $todoNode->addChild('title', htmlspecialchars($todo->title));
            $todoNode->addChild('content', htmlspecialchars($todo->content));
            $todoNode->addChild('category', htmlspecialchars($todo->category?->name));

            $tagsNode = $todoNode->addChild('tags');
            foreach ($todo->tags as $tag) {
                $tagsNode->addChild('tag', htmlspecialchars($tag->name));
            }

            $priority = match ($todo->priority) {
                1 => '高',
                2 => '中',
                3 => '低',
                default => ''
            };
            $todoNode->addChild('priority', $priority);
            $todoNode->addChild('start_date', $todo->start_date->format('Y-m-d'));
            $todoNode->addChild('end_date', $todo->end_date->format('Y-m-d'));
            $todoNode->addChild('completed_at', $todo->completed_at?->format('Y-m-d'));
            $todoNode->addChild('status', $todo->completed_at ? '完了' : '未完了');
        }

        $fileName = 'todos_' . date('YmdHis') . '.xml';

        return response($xml->asXML(), 200, [
            'Content-Type' => 'application/xml',
            'Content-Disposition' => 'attachment; filename="' . $fileName . '"',
        ]);
    }
}
