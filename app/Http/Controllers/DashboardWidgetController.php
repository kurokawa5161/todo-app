<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\DashboardWidget;

class DashboardWidgetController extends Controller
{
    //ユーザーのウィジェット一覧取得
    public function index()
    {
        $dashboardWidgets = DashboardWidget::where('user_id', auth()->id())
            ->orderBy('position', 'asc')
            ->get();

        return response()->json($dashboardWidgets);
    }

    //ウィジェット追加
    public function store(Request $request)
    {
        $request->validate([
            'widget_type' => 'required|string|in:stats,chart_weekly,chart_monthly,chart_yearly,heatmap,gantt,recent_todos,category_summary,priority_summary',
            'position' => 'integer|min:0',
            'size' => 'required|string|in:small,medium,large,full',
            'is_visible' => 'boolean',
            'settings' => 'nullable|array',
        ]);

        $dashboardWidget = new DashboardWidget();
        $dashboardWidget->user_id = auth()->id();
        $dashboardWidget->widget_type = $request->widget_type;
        $dashboardWidget->position = $request->position ?? DashboardWidget::where('user_id', auth()->id())->max('position') + 1;
        $dashboardWidget->size = $request->size ?? 'medium';
        $dashboardWidget->is_visible = $request->is_visible ?? true;
        $dashboardWidget->settings = $request->settings;

        $dashboardWidget->save();

        return response()->json([
            'success' => true,
            'message' => 'ウィジェットを作成しました',
            'widget' => $dashboardWidget
        ], 201);
    }

    //ウィジェット更新
    public function update(Request $request, DashboardWidget $dashboardWidget)
    {
        //権限チェック
        $this->authorize('update', $dashboardWidget);

        $validated = $request->validate([
            'widget_type' => 'required|string|in:stats,chart_weekly,chart_monthly,chart_yearly,heatmap,gantt,recent_todos,category_summary,priority_summary',
            'position' => 'integer|min:0',
            'size' => 'required|string|in:small,medium,large,full',
            'is_visible' => 'boolean',
            'settings' => 'nullable|array',
        ]);
        $dashboardWidget->update($validated);

        return response()->json([
            'success' => true,
            'message' => 'ウィジェットを更新しました',
            'widget' => $dashboardWidget
        ]);
    }

    //ウィジェット削除
    public function destroy(DashboardWidget $dashboardWidget)
    {
        //権限チェック
        $this->authorize('delete', $dashboardWidget);

        $dashboardWidget->delete();

        return response()->json([
            'success' => true,
            'message' => 'ウィジェットを削除しました',
        ]);
    }

    //並び順更新
    public function reorder(Request $request)
    {
        $validated = $request->validate([
            'widgets' => 'required|array',
            'widgets.*.id' => 'required|integer|exists:dashboard_widgets,id',
            'widgets.*.position' =>  'required|integer|min:0'
        ]);

        foreach ($validated['widgets'] as $widgetData) {
            $dashboardWidget = DashboardWidget::findOrFail($widgetData['id']);

            //権限チェック
            $this->authorize('update', $dashboardWidget);

            $dashboardWidget->position = $widgetData['position'];
            $dashboardWidget->save();
        }

        return response()->json([
            'success' => true,
            'message' => '並び順を更新しました',
        ]);
    }

    //表示/非表示切り替え
    public function toggle(Request $request, DashboardWidget $dashboardWidget)
    {
        //権限チェック
        $this->authorize('update', $dashboardWidget);

        $validated = $request->validate([
            'is_visible' => 'required|boolean'
        ]);

        $dashboardWidget->is_visible = $validated['is_visible'];
        $dashboardWidget->save();

        return response()->json([
            'success' => true,
            'is_visible' => $dashboardWidget->is_visible
        ]);
    }

    //デフォルトレイアウトに戻す
    public function reset()
    {

        //ユーザーの全ウィジェットを削除

        DashboardWidget::where('user_id', auth()->id())->delete();

        //デフォルトウィジェットを作成
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

        foreach ($defaultWidgets as $defaultWidget) {
            DashboardWidget::create([
                'user_id' => auth()->id(),
                'widget_type' => $defaultWidget['widget_type'],
                'position' => $defaultWidget['position'],
                'size' => $defaultWidget['size'],
                'is_visible' => true,
                'settings' => null
            ]);
        }

        return response()->json([
            'success' => true,
            'message' => 'デフォルトレイアウトに戻しました'
        ]);
    }
}
