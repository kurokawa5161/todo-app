# フェーズ21A実装ガイド：高度な統計レポート

## 実装日
準備中

## 目標
ダッシュボードに Chart.js を導入し、グラフ表示による視覚的な統計レポート機能を追加する。

---

## 実装する機能（全4項目）

### 1. Chart.js導入・基本グラフ実装
- [ ] Chart.js CDN追加（dashboard.blade.php）
- [ ] 完了率推移グラフ（折れ線グラフ）
- [ ] カテゴリ別円グラフ
- [ ] 優先度別棒グラフ

### 2. 週次サマリー強化
- [ ] 週次完了数の日別グラフ（過去4週間）
- [ ] 平均完了時間の計算
- [ ] 生産性スコア算出（完了数÷予定数）
- [ ] 前週比較機能

### 3. 月次レポート強化
- [ ] 月次完了数の日別グラフ（過去6ヶ月）
- [ ] カテゴリ別月次分析
- [ ] タグ別月次分析
- [ ] 前月比較機能

### 4. 年間サマリー（新規）
- [ ] 年間完了数の月別グラフ
- [ ] 年間統計（総完了数、平均完了率）
- [ ] カテゴリ別年間集計
- [ ] 年間エクスポート（PDF）

---

## 技術仕様

### Chart.js バージョン
- **推奨:** Chart.js 4.4.0以上
- **CDN:** `https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js`

### グラフ設定サンプル

#### 1. 完了率推移グラフ（折れ線）
```javascript
const completionRateChart = new Chart(ctx, {
    type: 'line',
    data: {
        labels: ['第1週', '第2週', '第3週', '第4週'],
        datasets: [{
            label: '完了率（%）',
            data: [65, 72, 80, 85],
            borderColor: 'rgb(59, 130, 246)', // blue-600
            backgroundColor: 'rgba(59, 130, 246, 0.1)',
            tension: 0.3
        }]
    },
    options: {
        responsive: true,
        maintainAspectRatio: false,
        plugins: {
            legend: {
                display: true,
                position: 'top'
            }
        },
        scales: {
            y: {
                beginAtZero: true,
                max: 100,
                ticks: {
                    callback: function(value) {
                        return value + '%';
                    }
                }
            }
        }
    }
});
```

#### 2. カテゴリ別円グラフ
```javascript
const categoryChart = new Chart(ctx, {
    type: 'doughnut',
    data: {
        labels: ['仕事', 'プライベート', '買い物'],
        datasets: [{
            data: [45, 30, 25],
            backgroundColor: [
                'rgb(239, 68, 68)',  // red-500
                'rgb(59, 130, 246)', // blue-500
                'rgb(34, 197, 94)'   // green-500
            ]
        }]
    },
    options: {
        responsive: true,
        maintainAspectRatio: false,
        plugins: {
            legend: {
                position: 'right'
            }
        }
    }
});
```

#### 3. 優先度別棒グラフ
```javascript
const priorityChart = new Chart(ctx, {
    type: 'bar',
    data: {
        labels: ['高', '中', '低'],
        datasets: [
            {
                label: '完了',
                data: [12, 25, 8],
                backgroundColor: 'rgb(34, 197, 94)' // green-500
            },
            {
                label: '未完了',
                data: [5, 10, 15],
                backgroundColor: 'rgb(249, 115, 22)' // orange-500
            }
        ]
    },
    options: {
        responsive: true,
        maintainAspectRatio: false,
        scales: {
            x: {
                stacked: false
            },
            y: {
                beginAtZero: true
            }
        }
    }
});
```

---

## データ準備（DashboardController 拡張）

### 週次データ取得メソッド追加
```php
// 過去4週間の週次完了数
private function getWeeklyCompletionData()
{
    $weeklyData = [];
    for ($i = 3; $i >= 0; $i--) {
        $start = now()->subWeeks($i)->startOfWeek();
        $end = now()->subWeeks($i)->endOfWeek();
        
        $count = Todo::where('user_id', auth()->id())
            ->whereNotNull('completed_at')
            ->whereBetween('completed_at', [$start, $end])
            ->count();
        
        $weeklyData[] = [
            'label' => '第' . (4 - $i) . '週',
            'count' => $count
        ];
    }
    return $weeklyData;
}
```

### 月次データ取得メソッド追加
```php
// 過去6ヶ月の月次完了数
private function getMonthlyCompletionData()
{
    $monthlyData = [];
    for ($i = 5; $i >= 0; $i--) {
        $start = now()->subMonths($i)->startOfMonth();
        $end = now()->subMonths($i)->endOfMonth();
        
        $count = Todo::where('user_id', auth()->id())
            ->whereNotNull('completed_at')
            ->whereBetween('completed_at', [$start, $end])
            ->count();
        
        $monthlyData[] = [
            'label' => $start->format('Y-m'),
            'count' => $count
        ];
    }
    return $monthlyData;
}
```

### 年間データ取得メソッド追加
```php
// 年間（過去12ヶ月）の完了数
private function getYearlyCompletionData()
{
    $yearlyData = [];
    for ($i = 11; $i >= 0; $i--) {
        $start = now()->subMonths($i)->startOfMonth();
        $end = now()->subMonths($i)->endOfMonth();
        
        $count = Todo::where('user_id', auth()->id())
            ->whereNotNull('completed_at')
            ->whereBetween('completed_at', [$start, $end])
            ->count();
        
        $yearlyData[] = [
            'label' => $start->format('Y-m'),
            'count' => $count
        ];
    }
    return $yearlyData;
}
```

---

## ダークモード対応

### Chart.js ダークモード設定
```javascript
// Tailwind のダークモード検出
const isDarkMode = document.documentElement.classList.contains('dark');

const chartOptions = {
    // ... 既存の options
    plugins: {
        legend: {
            labels: {
                color: isDarkMode ? '#e5e7eb' : '#374151' // gray-200 : gray-700
            }
        }
    },
    scales: {
        x: {
            ticks: {
                color: isDarkMode ? '#9ca3af' : '#6b7280' // gray-400 : gray-500
            },
            grid: {
                color: isDarkMode ? '#374151' : '#e5e7eb' // gray-700 : gray-200
            }
        },
        y: {
            ticks: {
                color: isDarkMode ? '#9ca3af' : '#6b7280'
            },
            grid: {
                color: isDarkMode ? '#374151' : '#e5e7eb'
            }
        }
    }
};
```

---

## レイアウト設計

### dashboard.blade.php 構成案
```html
<!-- 既存のサマリーカード（維持） -->
<div class="grid grid-cols-1 md:grid-cols-3 gap-4">
    <!-- 総Todo数、完了、未完了 -->
</div>

<!-- 完了率カード（維持） -->
<div class="grid grid-cols-1 md:grid-cols-4 gap-4">
    <!-- 全体、今週、今月、期限遵守率 -->
</div>

<!-- 【新規】グラフセクション -->
<div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
    <!-- 完了率推移グラフ -->
    <div class="bg-white dark:bg-gray-800 shadow-sm sm:rounded-lg p-6">
        <h3 class="text-lg font-semibold mb-4">📈 完了率推移（週次）</h3>
        <div class="h-64">
            <canvas id="completionRateChart"></canvas>
        </div>
    </div>
    
    <!-- カテゴリ別円グラフ -->
    <div class="bg-white dark:bg-gray-800 shadow-sm sm:rounded-lg p-6">
        <h3 class="text-lg font-semibold mb-4">📊 カテゴリ別分布</h3>
        <div class="h-64">
            <canvas id="categoryChart"></canvas>
        </div>
    </div>
</div>

<div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
    <!-- 優先度別棒グラフ -->
    <div class="bg-white dark:bg-gray-800 shadow-sm sm:rounded-lg p-6">
        <h3 class="text-lg font-semibold mb-4">🎯 優先度別集計</h3>
        <div class="h-64">
            <canvas id="priorityChart"></canvas>
        </div>
    </div>
    
    <!-- 月次完了数グラフ -->
    <div class="bg-white dark:bg-gray-800 shadow-sm sm:rounded-lg p-6">
        <h3 class="text-lg font-semibold mb-4">📅 月次完了数（過去6ヶ月）</h3>
        <div class="h-64">
            <canvas id="monthlyChart"></canvas>
        </div>
    </div>
</div>

<!-- 既存のテーブル（維持） -->
<div class="bg-white dark:bg-gray-800 shadow-sm sm:rounded-lg">
    <!-- カテゴリ別集計テーブル -->
</div>
```

---

## パフォーマンス最適化

### キャッシュ活用
```php
// DashboardController.php
use Illuminate\Support\Facades\Cache;

public function index()
{
    $userId = auth()->id();
    
    // 統計データは1時間キャッシュ
    $weeklyData = Cache::remember("weekly_data_{$userId}", 3600, function() {
        return $this->getWeeklyCompletionData();
    });
    
    // ... 他のデータも同様
}
```

---

## テスト項目

### 動作確認チェックリスト
- [ ] グラフが正しく表示される（ライトモード）
- [ ] グラフが正しく表示される（ダークモード）
- [ ] レスポンシブ対応（モバイル・タブレット）
- [ ] データがない場合のエラーハンドリング
- [ ] ブラウザ互換性（Chrome、Edge、Firefox）
- [ ] パフォーマンス（大量データでも快適）

---

## 参考リソース

### Chart.js 公式ドキュメント
- [Chart.js Documentation](https://www.chartjs.org/docs/latest/)
- [Chart.js Samples](https://www.chartjs.org/docs/latest/samples/)

### Tailwind CSS カラーパレット
- [Tailwind Colors](https://tailwindcss.com/docs/customizing-colors)

### Laravel Collection メソッド
- [Laravel Collections](https://laravel.com/docs/11.x/collections)

---

## 次のステップ（フェーズ21B・21C）

### フェーズ21B: データ可視化
- カスタムダッシュボード作成機能
- ヒートマップ（日別完了状況）
- ガントチャート（タスク進捗）

### フェーズ21C: エクスポート機能拡張
- Excel形式エクスポート（PhpSpreadsheet）
- JSON/XML形式対応
- レポートテンプレート機能

---

最終更新: 2026-04-29
