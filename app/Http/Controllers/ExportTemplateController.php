<?php

namespace App\Http\Controllers;

use App\Models\ExportTemplate;
use App\Models\Todo;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Response;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Alignment;

class ExportTemplateController extends Controller
{
    public function index()
    {
        $templates = ExportTemplate::where('user_id', auth()->id())
            ->orderBy('created_at', 'asc')
            ->get();

        return view('export-templates.index', compact('templates'));
    }

    public function create()
    {
        return view('export-templates.create');
    }

    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255|unique:export_templates,name,NULL,id,user_id,' . auth()->id(),
            'description' => 'required|string',
            'format' => 'required|in:csv,excel,json,xml',
            'fields' => 'required|array|min:1',
            'fields.*' => 'in:id,title,content,category,tags,priority,start_date,end_date,completed_at,status',
            'order' => 'nullable|array',
            'order.*' => 'in:id,title,content,category,tags,priority,start_date,end_date,completed_at,status',
            'filters' => 'nullable|array',
        ]);

        $template = new ExportTemplate();
        $template->user_id = auth()->id();
        $template->name = $request->name;
        $template->description = $request->description;
        $template->format = $request->format;
        $template->fields = $request->fields;
        $template->order = $request->order;
        $template->filters = $request->filters;

        $template->save();

        return redirect()->route('export-templates.index');
    }

    public function edit(Request $request, ExportTemplate $template)
    {
        //権限チェック
        $this->authorize('view', $template);

        return view('export-templates.edit', compact('template'));
    }

    public function update(Request $request, ExportTemplate $template)
    {
        //権限チェック
        $this->authorize('update', $template);

        $validated = $request->validate([
            'name' => 'required|string|max:255|unique:export_templates,name,' . $template->id . ',id,user_id,' . auth()->id(),
            'description' => 'required|string',
            'format' => 'required|in:csv,excel,json,xml',
            'fields' => 'required|array|min:1',
            'fields.*' => 'in:id,title,content,category,tags,priority,start_date,end_date,completed_at,status',
            'order' => 'nullable|array',
            'order.*' => 'in:id,title,content,category,tags,priority,start_date,end_date,completed_at,status',
            'filters' => 'nullable|array',
        ]);
        $template->update($validated);

        return redirect()->route('export-templates.index')
            ->with('success', 'テンプレートを更新しました');
    }

    public function destroy(ExportTemplate $template)
    {
        //権限チェック
        $this->authorize('delete', $template);

        $template->delete();

        return redirect()->route('export-templates.index');
    }

    public function export(ExportTemplate $template)
    {
        //権限チェック
        $this->authorize('view', $template);

        // Todoデータを取得（フィルター適用）
        $query = Todo::where('user_id', auth()->id());

        // フィルター適用
        if ($template->filters && isset($template->filters['status'])) {
            if ($template->filters['status'] === 'completed') {
                $query->whereNotNull('completed_at');
            } elseif ($template->filters['status'] === 'active') {
                $query->whereNull('completed_at');
            }
        }

        // 並び順適用
        if ($template->order && count($template->order) > 0) {
            foreach ($template->order as $orderField) {
                $query->orderBy($orderField);
            }
        } else {
            $query->orderBy('end_date');
        }

        $todos = $query->with(['category', 'tags'])->get();

        // フォーマットに応じてエクスポート
        switch ($template->format) {
            case 'csv':
                return $this->exportCsv($template, $todos);
            case 'excel':
                return $this->exportExcel($template, $todos);
            case 'json':
                return $this->exportJson($template, $todos);
            case 'xml':
                return $this->exportXml($template, $todos);
            default:
                abort(400, '不正なフォーマットです');
        }
    }

    private function exportCsv(ExportTemplate $template, $todos)
    {
        $callback = function () use ($template, $todos) {
            $file = fopen('php://output', 'w');

            //BOM追加（Excel用）
            fprintf($file, chr(0xEF) . chr(0xBB) . chr(0xBF));

            //ヘッダー行
            $headers = $this->getFieldHeaders($template->fields);
            fputcsv($file, $headers);

            foreach ($todos as $todo) {
                $row = $this->getTodoRow($todo, $template->fields);
                fputcsv($file, $row);
            }
            fclose($file);
        };

        $timestamp = date('YmdHis');
        $encodedFilename = rawurlencode($template->name) . '_' . $timestamp . '.csv';

        return Response::stream($callback, 200, [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => "attachment; filename=\"template_{$timestamp}.csv\"; filename*=UTF-8''{$encodedFilename}",
        ]);
    }

    private function exportExcel(ExportTemplate $template, $todos)
    {
        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();

        // タイトル行
        $sheet->setCellValue('A1', $template->name);
        $sheet->mergeCells('A1:' . chr(64 + count($template->fields)) . '1');
        $sheet->getStyle('A1')->getFont()->setBold(true)->setSize(16);
        $sheet->getStyle('A1')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

        // ヘッダー行
        $headers = $this->getFieldHeaders($template->fields);
        $column = 'A';
        foreach ($headers as $header) {
            $sheet->setCellValue($column . '3', $header);
            $sheet->getStyle($column . '3')->getFont()->setBold(true);
            $sheet->getStyle($column . '3')->getFill()
                ->setFillType(Fill::FILL_SOLID)
                ->getStartColor()->setARGB('FFE0E0E0');
            $column++;
        }

        // データ行
        $row = 4;
        foreach ($todos as $todo) {
            $rowData = $this->getTodoRow($todo, $template->fields);
            $column = 'A';
            foreach ($rowData as $value) {
                $sheet->setCellValue($column . $row, $value);
                $column++;
            }
            $row++;
        }

        // 列幅自動調整
        foreach (range('A', chr(64 + count($template->fields))) as $col) {
            $sheet->getColumnDimension($col)->setAutoSize(true);
        }

        // 枠線追加
        $lastColumn = chr(64 + count($template->fields));
        $sheet->getStyle('A3:' . $lastColumn . ($row - 1))
            ->getBorders()
            ->getAllBorders()
            ->setBorderStyle(Border::BORDER_THIN);

        // ファイル出力
        $writer = new Xlsx($spreadsheet);
        $timestamp = date('YmdHis');
        $encodedFilename = rawurlencode($template->name) . '_' . $timestamp . '.xlsx';

        return response()->streamDownload(function () use ($writer) {
            $writer->save('php://output');
        }, "template_{$timestamp}.xlsx", [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'Content-Disposition' => "attachment; filename=\"template_{$timestamp}.xlsx\"; filename*=UTF-8''{$encodedFilename}",
        ]);
    }

    private function exportJson(ExportTemplate $template, $todos)
    {
        $data = $todos->map(function ($todo) use ($template) {
            $row = [];
            foreach ($template->fields as $field) {
                $row[$field] = $this->getFieldValue($todo, $field);
            }
            return $row;
        });

        $timestamp = date('YmdHis');
        $encodedFilename = rawurlencode($template->name) . '_' . $timestamp . '.json';

        return response()->json($data, 200, [
            'Content-Type' => 'application/json',
            'Content-Disposition' => "attachment; filename=\"template_{$timestamp}.json\"; filename*=UTF-8''{$encodedFilename}",
        ], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
    }

    private function exportXml(ExportTemplate $template, $todos)
    {
        $xml = new \SimpleXMLElement('<?xml version="1.0" encoding="UTF-8"?><todos/>');

        foreach ($todos as $todo) {
            $todoNode = $xml->addChild('todo');
            foreach ($template->fields as $field) {
                $value = $this->getFieldValue($todo, $field);
                $todoNode->addChild($field, htmlspecialchars($value ?? ''));
            }
        }

        $timestamp = date('YmdHis');
        $encodedFilename = rawurlencode($template->name) . '_' . $timestamp . '.xml';

        return response($xml->asXML(), 200, [
            'Content-Type' => 'application/xml',
            'Content-Disposition' => "attachment; filename=\"template_{$timestamp}.xml\"; filename*=UTF-8''{$encodedFilename}",
        ]);
    }

    private function getFieldHeaders($fields)
    {
        $headerMap = [
            'id' => 'ID',
            'title' => 'タイトル',
            'content' => '内容',
            'category' => 'カテゴリー',
            'tags' => 'タグ',
            'priority' => '優先度',
            'start_date' => '開始日',
            'end_date' => '終了日',
            'completed_at' => '完了日',
            'status' => 'ステータス',
        ];

        return array_map(fn($field) => $headerMap[$field] ?? $field, $fields);
    }

    private function getTodoRow($todo, $fields)
    {
        return array_map(fn($field) => $this->getFieldValue($todo, $field), $fields);
    }

    private function getFieldValue($todo, $field)
    {
        switch ($field) {
            case 'id':
                return $todo->id;
            case 'title':
                return $todo->title;
            case 'content':
                return $todo->content;
            case 'category':
                return $todo->category?->name ?? '未分類';
            case 'tags':
                return $todo->tags->pluck('name')->join(', ');
            case 'priority':
                return match ($todo->priority) {
                    1 => '高',
                    2 => '中',
                    3 => '低',
                    default => ''
                };
            case 'start_date':
                return $todo->start_date?->format('Y-m-d') ?? '';
            case 'end_date':
                return $todo->end_date?->format('Y-m-d') ?? '';
            case 'completed_at':
                return $todo->completed_at?->format('Y-m-d') ?? '';
            case 'status':
                return $todo->completed_at ? '完了' : '未完了';
            default:
                return '';
        }
    }
}
